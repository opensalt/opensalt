<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\CfRubric;
use App\Service\Api1Uris;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class CfRubricNormalizer implements NormalizerAwareInterface, ContextAwareNormalizerInterface
{
    use NormalizerAwareTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private readonly Api1Uris $api1Uris,
    ) {
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof CfRubric;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof CfRubric) {
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
                ? 'CFRubric'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'title' => $object->getTitle(),
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'description' => $object->getDescription(),
        ];

        foreach ($object->getCriteria() as $criterion) {
            $data['CFRubricCriteria'][] = $this->normalizer->normalize($criterion, $format, $context);
        }

        return array_filter($data, static fn ($val) => null !== $val);
    }
}
