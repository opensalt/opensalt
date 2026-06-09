<?php

declare(strict_types=1);

namespace App\Serializer\CaseJson;

use App\Entity\Framework\CfRubricCriterionLevel;
use App\Service\Api1Uris;
use App\Util\Collection;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class CfRubricCriterionLevelNormalizer implements NormalizerInterface
{
    use LastChangeDateTimeTrait;

    public function __construct(
        private Api1Uris $api1Uris,
    ) {
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CfRubricCriterionLevel;
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [CfRubricCriterionLevel::class => true];
    }

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): ?array
    {
        if (!$data instanceof CfRubricCriterionLevel) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $addType = $addContext ?? $context['add-case-type'] ?? null;
        $case10 = in_array('CASE-1.0', $context['groups'] ?? []);
        $return = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addType)
                ? 'CFRubricCriterionLevel'
                : null,
            'identifier' => $data->getIdentifier(),
            'uri' => $this->api1Uris->getUri($data),
            'rubricCriterionId' => in_array('CfRubricCriterionLevel', $context['groups'] ?? [], true)
                ? $data->getCriterion()->getIdentifier()
                : null,
            'lastChangeDateTime' => $this->getLastChangeDateTime($data),
            'description' => Collection::emptyToNull($data->getDescription()),
            'feedback' => Collection::emptyToNull($data->getFeedback()),
            'quality' => Collection::emptyToNull($data->getQuality()),
            'score' => $data->getScore(),
            'position' => $data->getPosition(),
            'extensions' => $case10 ? null : $data->getExtensions(),
        ];

        if (in_array('opensalt', $context['groups'] ?? [], true)) {
            $return['_opensalt'] = $data->getExtra();
        }

        return Collection::removeEmptyElements($return);
    }
}
