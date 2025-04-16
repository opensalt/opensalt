<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsItem;
use App\Service\Api1Uris;
use App\Util\Collection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class LsItemNormalizer implements NormalizerInterface
{
    use DateCallbackTrait;
    use AssociationLinkTrait;
    use LinkUriTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly Api1Uris $api1Uris,
    ) {
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LsItem;
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [LsItem::class => true];
    }

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): ?array
    {
        if (!$data instanceof LsItem) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $addType = (null === $addContext) ? ($context['add-case-type'] ?? null) : $addContext;
        $case10 = in_array('CASE-1.0', $context['groups'] ?? []);
        $conceptKeywords = $data->getConceptKeywordsArray();
        $conceptKeywordsUri = $data->getConcepts();
        $subject = $data->getSubject();
        $subjectURIs = $data->getSubjects();
        $ret = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addType)
                ? 'CFItem'
                : null,
            'identifier' => $data->getIdentifier(),
            'uri' => $this->api1Uris->getUri($data),
            'CFDocumentURI' => $this->createDocumentLinkUri($data->getLsDoc(), 'LsItem', $context),
            'fullStatement' => $data->getFullStatement(),
            'alternativeLabel' => $data->getAlternativeLabel(),
            'CFItemType' => $data->getItemType()?->getTitle(),
            'CFItemTypeURI' => $this->api1Uris->getLinkUri($data->getItemType()),
            'humanCodingScheme' => $data->getHumanCodingScheme(),
            'listEnumeration' => $data->getListEnumInSource(),
            'abbreviatedStatement' => $data->getAbbreviatedStatement(),
            'conceptKeywords' => count($conceptKeywords) > 0
                ? $conceptKeywords
                : null,
            'conceptKeywordsURI' => count($conceptKeywordsUri) > 0
                ? $this->api1Uris->getLinkUri($conceptKeywordsUri[0])
                : null,
            'notes' => $data->getNotes(),
            'subject' => $case10 ? null : (count($subject ?? []) > 0
                ? $subject
                : null),
            'subjectURI' => $case10 ? null : (count($subjectURIs) > 0
                ? $this->api1Uris->getLinkUriList($subjectURIs)
                : null),
            'language' => $data->getLanguage(),
            'educationLevel' => $this->api1Uris->splitByComma($data->getEducationalAlignment()),
            'licenseURI' => $this->api1Uris->getLinkUri($data->getLicence()),
            'statusStartDate' => $this->toDate($data->getStatusStart()),
            'statusEndDate' => $this->toDate($data->getStatusEnd()),
            'lastChangeDateTime' => $this->getLastChangeDateTime($data),
            'associationSet' => $this->createAssociationLinks($data, $context),
            'extensions' => $case10 ? null : $data->getExtensions(),
        ];

        if (in_array('opensalt', $context['groups'] ?? [], true)) {
            $ret['_opensalt'] = $data->getExtra();
        }

        return Collection::removeEmptyElements($ret);
    }
}
