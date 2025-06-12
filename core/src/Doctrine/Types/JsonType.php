<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType as DbalJsonType;

class JsonType extends DbalJsonType
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ([] === $value) {
            return null;
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
