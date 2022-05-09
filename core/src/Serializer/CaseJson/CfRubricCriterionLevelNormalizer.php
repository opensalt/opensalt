<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\CfRubricCriterionLevel;
use App\Service\Api1Uris;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class CfRubricCriterionLevelNormalizer implements ContextAwareNormalizerInterface
{
    use LastChangeDateTimeTrait;

    public function __construct(
        private readonly Api1Uris $api1Uris,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof CfRubricCriterionLevel;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof CfRubricCriterionLevel) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addContext)
                ? 'CFRubricCriterionLevel'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'rubricCriterionId' => in_array('CfRubricCriterionLevel', $context['groups'] ?? [], true)
                ? $object->getCriterion()->getIdentifier()
                : null,
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'description' => $object->getDescription(),
            'feedback' => $object->getFeedback(),
            'quality' => $object->getQuality(),
            'score' => $object->getScore(),
            'position' => $object->getPosition(),
        ];

        return array_filter($data, static fn ($val) => null !== $val);
    }
}
