<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\CfRubricCriterion;
use App\Service\Api1Uris;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CfRubricCriterionNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    use NormalizerAwareTrait;
    use LinkUriTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private readonly Api1Uris $api1Uris,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof CfRubricCriterion;
    }

    public function normalize(mixed $object, string $format = null, array $context = []): ?array
    {
        if (!$object instanceof CfRubricCriterion) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        if (null !== ($context['add-case-context'] ?? null)) {
            unset($context['add-case-context']);
        }
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addContext)
                ? 'CFRubricCriterion'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'CFItemURI' => $this->createLinkUri($object->getItem(), $context),
            'rubricId' => in_array('CfRubricCriterion', $context['groups'] ?? [], true)
                ? $object->getRubric()?->getIdentifier()
                : null,
            'category' => $object->getCategory(),
            'description' => $object->getDescription(),
            'position' => $object->getPosition(),
            'weight' => $object->getWeight(),
        ];

        foreach ($object->getLevels() as $level) {
            $data['CFRubricCriterionLevels'][] = $this->normalizer->normalize($level, $format, $context);
        }

        return array_filter($data, static fn ($val) => null !== $val);
    }
}
