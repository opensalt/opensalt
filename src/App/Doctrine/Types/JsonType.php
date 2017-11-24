<?php

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType as DbalJsonType;

class JsonType extends DbalJsonType
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ([] === $value) {
            return null;
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
