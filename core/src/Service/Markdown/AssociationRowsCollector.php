<?php

declare(strict_types=1);

namespace App\Service\Markdown;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Serializer\CaseJson\AssociationLinkTrait;
use App\Service\Api1Uris;
use App\Service\IdentifiableObjectHelper;
use App\Service\UriGenerator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AssociationRowsCollector
{
    use AssociationLinkTrait;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        private Api1Uris $api1Uris,
        private UriGenerator $uriGenerator,
        private IdentifiableObjectHelper $objectHelper,
    ) {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Collect outgoing and incoming association rows for an object, filtered
     * by the same visibility rules as the CASE JSON serialization.
     *
     * @return array{outgoing: array<int, array<string, mixed>>, incoming: array<int, array<string, mixed>>}
     */
    public function collect(LsDoc|LsItem $object): array
    {
        $outgoing = [];
        foreach ($object->getAssociations() as $association) {
            $row = $this->createRow($association, 'destination');
            if (null !== $row) {
                $outgoing[] = $row;
            }
        }

        $incoming = [];
        foreach ($object->getInverseAssociations() as $association) {
            $row = $this->createRow($association, 'origin');
            if (null !== $row) {
                $incoming[] = $row;
            }
        }

        $sort = static fn (array $a, array $b) => [$a['relationship'], $a['title']] <=> [$b['relationship'], $b['title']];
        usort($outgoing, $sort);
        usort($incoming, $sort);

        return ['outgoing' => $outgoing, 'incoming' => $incoming];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function createRow(LsAssociation $association, string $which): ?array
    {
        $base = null;
        try {
            $base = $this->createAssociationLink($association, $which, null);
        } catch (\InvalidArgumentException) {
            // Unknown CASE type inversion -- fall through to a raw row below.
        }

        if (null === $base) {
            return null;
        }

        $target = 'destination' === $which ? $association->getDestination() : $association->getOrigin();
        $targetUri = 'destination' === $which ? $association->getDestinationNodeUri() : $association->getOriginNodeUri();

        $kind = 'external';
        $local = '';
        $uri = $targetUri ?? '';
        $title = $base['title'] ?? $uri;
        $code = null;
        $framework = null;
        $frameworkUri = null;
        $listEnumeration = null;
        $unresolved = null;

        // The node may be stored as a bare identifier (its FK never set) even
        // though the object exists locally -- resolve it before treating the
        // reference as unresolvable.  Only bare UUIDs are looked up; URLs and
        // other external node identifiers stay external.
        if (is_string($target)) {
            $nodeIdentifier = 'destination' === $which
                ? $association->getDestinationNodeIdentifier()
                : $association->getOriginNodeIdentifier();
            $isBareUuid = null !== $nodeIdentifier
                && 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $nodeIdentifier);
            $resolved = $isBareUuid
                ? $this->objectHelper->findObjectByIdentifier($nodeIdentifier)
                : null;
            if (null !== $resolved) {
                $target = $resolved;
            } elseif ($isBareUuid) {
                $unresolved = $nodeIdentifier;
            }
        }

        if ($target instanceof LsItem || $target instanceof LsDoc) {
            $kind = $target instanceof LsDoc
                ? 'document'
                : (LsItemKind::tryFrom($target->getDiscriminator())?->objectType() ?? 'item');
            $local = '1';
            $uri = $this->uriGenerator->getUri($target) ?? '';
            $title = $target instanceof LsDoc
                ? ($target->getTitle() ?? $target->getIdentifier())
                : DisplayTitle::from($target->getAbbreviatedStatement(), $target->getFullStatement(), $target->getIdentifier());
            if ('' === $title) {
                $title = $target->getIdentifier();
            }

            $targetDoc = $target instanceof LsDoc ? $target : $target->getLsDoc();
            if (null !== $targetDoc) {
                $framework = $targetDoc->getTitle();
                $frameworkUri = $this->uriGenerator->getUri($targetDoc);
            }
        }

        if ($target instanceof LsItem) {
            $code = $target->getHumanCodingScheme();
            $listEnumeration = $target->getListEnumInSource();
        }

        if (null !== $unresolved) {
            $kind = 'unresolved';
            $uri = '';
            $title = 'Unresolved reference';
        }

        return [
            'relationship' => $base['associationType'] ?? $association->getType(),
            'direction' => 'destination' === $which ? 'outgoing' : 'incoming',
            'title' => $title,
            'code' => $code,
            'uri' => $uri,
            'unresolved' => $unresolved,
            'kind' => $kind,
            'group' => $association->getGroup()?->getTitle(),
            'framework' => $framework,
            'frameworkUri' => $frameworkUri,
            'local' => $local,
            'subtype' => $association->getSubtype(),
            'notes' => $association->getNotes(),
            'extensions' => [] !== $association->getExtensions() ? $association->getExtensions() : null,
            'sequenceNumber' => $association->getSequenceNumber(),
            'listEnumeration' => $listEnumeration,
        ];
    }
}
