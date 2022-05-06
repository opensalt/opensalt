<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsAssociation;
use App\Service\Api1Uris;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class LsAssociationNormalizer implements ContextAwareNormalizerInterface
{
    use LinkUriTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private Api1Uris $api1Uris,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof LsAssociation;
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!$object instanceof LsAssociation) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addContext)
                ? 'CFAssociation'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'CFDocumentURI' => $this->createDocumentLinkUri($object->getLsDoc(), 'LsAssociation', $context),
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'sequenceNumber' => $object->getSequenceNumber(),
            'CFAssociationGroupingURI' => $this->createLinkUri($object->getGroup(), $context),
            'originNodeURI' => $this->createOutLink($object, 'origin', $context),
            'associationType' => $object->getNormalizedType(),
            'destinationNodeURI' => $this->createOutLink($object, 'destination', $context),
        ];

        if (in_array('opensalt', $context['groups'] ?? [], true)) {
            $data['_opensalt'] = [
                'subtype' => $object->getSubtype(),
                'annotation' => $object->getAnnotation(),
            ];

            $data['_opensalt'] = array_filter($data['_opensalt'], static fn ($val) => null !== $val);
        }

        return array_filter($data, static fn ($val) => null !== $val);
    }

    protected function createOutLink(LsAssociation $association, string $which, array $context): ?array
    {
        if (!in_array($which, ['origin', 'destination'])) {
            throw new \InvalidArgumentException('Expecting "origin" or "destination" for which part of the association is wanted');
        }

        $targetLink = $this->api1Uris->getNodeLinkUri($which, $association);

        if (null === $targetLink) {
            return null;
        }

        if (null === ($context['case-json-ld'] ?? null)) {
            return $targetLink;
        }

        return [
            'type' => 'LinkURI',
            'title' => $targetLink['title'],
            'identifier' => $targetLink['identifier'],
            'uri' => $targetLink['uri'],
            'targetId' => $targetLink['uri'],
        ];
    }
}
