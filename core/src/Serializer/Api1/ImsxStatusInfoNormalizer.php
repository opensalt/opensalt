<?php

namespace App\Serializer\Api1;

use App\DTO\Api1\ImsxStatusInfo;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class ImsxStatusInfoNormalizer implements ContextAwareNormalizerInterface
{
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof ImsxStatusInfo;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof ImsxStatusInfo) {
            throw new \InvalidArgumentException('ImsxStatusInfo object expected');
        }

        $data = [
            'imsx_codeMajor' => $object->codeMajor,
            'imsx_severity' => $object->severity,
            'imsx_description' => $object->description,
        ];

        if (!empty($object->codeMinor)) {
            foreach ($object->codeMinor->codeMinorField as $minor) {
                $data['imsx_codeMinor']['imsx_codeMinorField'][] = [
                    'ims_codeMinorFieldName' => $minor->name,
                    'ims_codeMinorFieldValue' => $minor->value,
                ];
            }
        }

        return $data;
    }
}
