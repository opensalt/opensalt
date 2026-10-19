<?php

declare(strict_types=1);

namespace App\Serializer\Markdown;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\CfRubricCriterion;
use App\Entity\Framework\CfRubricCriterionLevel;
use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsItemKind;
use App\Serializer\CaseJson\LastChangeDateTimeTrait;
use App\Service\Markdown\AssociationRowsCollector;
use App\Service\Markdown\DisplayTitle;
use App\Service\Markdown\HtmlToMarkdownConverter;
use App\Service\Markdown\MarkdownBuilder;
use App\Service\UriGenerator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer', ['priority' => 64])]
final readonly class GenericMarkdownNormalizer implements NormalizerInterface
{
    use LastChangeDateTimeTrait;

    private const array TYPES = [
        LsAssociation::class => 'Association',
        LsDefItemType::class => 'ItemType',
        LsDefSubject::class => 'Subject',
        LsDefLicence::class => 'License',
        LsDefConcept::class => 'Concept',
        LsDefAssociationGrouping::class => 'AssociationGrouping',
        CfRubric::class => 'Rubric',
        CfRubricCriterion::class => 'RubricCriterion',
        CfRubricCriterionLevel::class => 'RubricCriterionLevel',
    ];

    private const array SYMMETRIC_TYPES = ['exactMatchOf' => true, 'isRelatedTo' => true, 'isPeerOf' => true];

    public function __construct(
        private UriGenerator $uriGenerator,
        private AssociationRowsCollector $associationRowsCollector,
        private HtmlToMarkdownConverter $htmlConverter,
    ) {
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return 'md' === $format && is_object($data) && isset(self::TYPES[$data::class]);
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return array_fill_keys(array_keys(self::TYPES), true);
    }

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): ?array
    {
        if (!is_object($data) || !isset(self::TYPES[$data::class]) || !$data instanceof IdentifiableInterface) {
            return null;
        }

        if ($data instanceof LsAssociation) {
            return $this->normalizeAssociation($data);
        }

        $frontMatter = array_filter([
            'objectType' => self::TYPES[$data::class],
            'identifier' => $data->getIdentifier(),
            'uri' => $this->uriGenerator->getUri($data),
            'lastChangeDateTime' => $this->getLastChangeDateTime($data),
        ], static fn (mixed $v): bool => null !== $v && [] !== $v);

        $fields = $this->fieldsOf($data);
        $title = (string) ($fields['Title'] ?? $data->getIdentifier() ?? 'Object');
        $description = $fields['Description'] ?? null;
        unset($fields['Title'], $fields['Description']);

        $blocks = [MarkdownBuilder::h(1, $title)];
        if (null !== $description && '' !== $description && !is_array($description)) {
            $blocks[] = MarkdownBuilder::p((string) $description);
        }

        $tableRows = [];
        foreach ($fields as $label => $value) {
            if (null === $value || '' === $value || [] === $value) {
                continue;
            }
            $tableRows[] = [$label, is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value];
        }
        if ([] !== $tableRows) {
            $blocks[] = MarkdownBuilder::table(['Field', 'Value'], $tableRows);
        }

        return ['front_matter' => $frontMatter, 'body' => MarkdownBuilder::joinBlocks($blocks)];
    }

    private function normalizeAssociation(LsAssociation $data): array
    {
        $type = $data->getNormalizedType();
        $symmetric = isset(self::SYMMETRIC_TYPES[$type]);

        $frontMatter = array_filter([
            'objectType' => 'Association',
            'associationType' => $type,
            'symmetric' => $symmetric ?: null,
            'identifier' => $data->getIdentifier(),
            'uri' => $this->uriGenerator->getUri($data),
            'frameworkIdentifier' => $data->getLsDoc()?->getIdentifier(),
            'frameworkTitle' => $data->getLsDoc()?->getTitle(),
            'sequenceNumber' => $data->getSequenceNumber(),
            'associationGroup' => $data->getGroup()?->getTitle(),
            'subtype' => $data->getSubtype(),
            'lastChangeDateTime' => $this->getLastChangeDateTime($data),
        ], static fn (mixed $v): bool => null !== $v && [] !== $v);

        $origin = $this->endpointSummary($data, 'origin');
        $destination = $this->endpointSummary($data, 'destination');

        $blocks = [
            MarkdownBuilder::h(1, $type),
            MarkdownBuilder::p($origin['summary']." — **{$type}** → ".$destination['summary']),
        ];

        $doc = $data->getLsDoc();
        if (null !== $doc) {
            $label = $doc->getTitle() ?? $doc->getIdentifier() ?? 'Framework';
            $uri = $this->uriGenerator->getUri($doc);
            $blocks[] = MarkdownBuilder::p('Recorded in: '.(null !== $uri ? MarkdownBuilder::link($label, $uri) : $label));
        }

        $blocks[] = MarkdownBuilder::joinBlocks([
            MarkdownBuilder::h(2, 'Endpoints'),
            MarkdownBuilder::bullets([
                'Origin: '.$origin['detail'],
                'Destination: '.$destination['detail'],
            ]),
        ]);

        $notes = $this->notesSection($data);
        if ('' !== $notes) {
            $blocks[] = $notes;
        }

        return ['front_matter' => $frontMatter, 'body' => MarkdownBuilder::joinBlocks($blocks)];
    }

    /**
     * @return array{summary: string, detail: string}
     */
    private function endpointSummary(LsAssociation $association, string $which): array
    {
        $node = 'origin' === $which ? $association->getOrigin() : $association->getDestination();

        if ($node instanceof \App\Entity\Framework\LsDoc) {
            $title = $node->getTitle() ?? $node->getIdentifier() ?? 'Document';
            $uri = $this->uriGenerator->getUri($node);

            return [
                'summary' => null !== $uri ? MarkdownBuilder::link($title, $uri) : $title,
                'detail' => 'document · '.$title,
            ];
        }

        if ($node instanceof \App\Entity\Framework\LsItem) {
            $kind = LsItemKind::tryFrom($node->getDiscriminator())?->objectType() ?? 'item';
            $title = DisplayTitle::from($node->getAbbreviatedStatement(), $node->getFullStatement(), $node->getHumanCodingScheme());
            if ('' === $title) {
                $title = $node->getIdentifier() ?? 'Item';
            }
            $code = $node->getHumanCodingScheme();
            $uri = $this->uriGenerator->getUri($node);

            $detailParts = [$kind];
            if (null !== $code && '' !== $code) {
                $detailParts[] = $code;
            }
            $detailParts[] = '"'.$title.'"';
            $framework = $node->getLsDoc()?->getTitle();
            if (null !== $framework && '' !== $framework) {
                $frameworkUri = null !== $node->getLsDoc() ? $this->uriGenerator->getUri($node->getLsDoc()) : null;
                $detailParts[] = 'Framework: '.(null !== $frameworkUri ? MarkdownBuilder::link($framework, $frameworkUri) : $framework);
            }

            return [
                'summary' => null !== $uri ? MarkdownBuilder::link($title, $uri) : $title,
                'detail' => implode(' · ', $detailParts),
            ];
        }

        $targetUri = (string) $node;

        return [
            'summary' => MarkdownBuilder::inlineCode($targetUri),
            'detail' => 'URI · '.$targetUri,
        ];
    }

    private function notesSection(LsAssociation $association): string
    {
        $notes = $this->htmlConverter->convert($association->getNotes());
        $extensions = $association->getExtensions();

        $lines = [];
        if ('' !== $notes) {
            $lines[] = '- notes: '.$notes;
        }
        foreach (is_array($extensions) ? $extensions : [] as $key => $value) {
            if (null === $value) {
                continue;
            }
            $value = is_scalar($value) ? (string) $value : MarkdownBuilder::inlineCode((string) json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            $lines[] = '- '.MarkdownBuilder::inlineCode((string) $key).': '.$value;
        }

        if ([] === $lines) {
            return '';
        }

        return MarkdownBuilder::joinBlocks([MarkdownBuilder::h(2, 'Notes'), implode("\n", $lines)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsOf(IdentifiableInterface $data): array
    {
        return match (true) {
            $data instanceof LsDefItemType => [
                'Title' => $data->getTitle(),
                'Description' => $data->getDescription(),
                'Code' => $data->getCode(),
                'Hierarchy code' => $data->getHierarchyCode(),
            ],
            $data instanceof LsDefSubject => [
                'Title' => $data->getTitle(),
                'Description' => $data->getDescription(),
                'Hierarchy code' => $data->getHierarchyCode(),
            ],
            $data instanceof LsDefLicence => [
                'Title' => $data->getTitle(),
                'Description' => $data->getDescription(),
                'Licence text' => $data->getLicenceText(),
            ],
            $data instanceof LsDefConcept => [
                'Title' => $data->getTitle(),
                'Description' => $data->getDescription(),
                'Keywords' => $data->getKeywords(),
                'Hierarchy code' => $data->getHierarchyCode(),
            ],
            $data instanceof LsDefAssociationGrouping => [
                'Title' => $data->getTitle(),
                'Description' => $data->getDescription(),
            ],
            $data instanceof CfRubric => [
                'Title' => $data->getTitle(),
                'Description' => $data->getDescription(),
                'Criteria' => $data->getCriteria()->count(),
            ],
            $data instanceof CfRubricCriterion => [
                'Description' => $data->getDescription(),
                'Category' => $data->getCategory(),
                'Position' => $data->getPosition(),
                'Levels' => $data->getLevels()->count(),
            ],
            $data instanceof CfRubricCriterionLevel => [
                'Description' => $data->getDescription(),
                'Quality' => $data->getQuality(),
                'Score' => $data->getScore(),
                'Position' => $data->getPosition(),
            ],
        };
    }
}
