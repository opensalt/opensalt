<?php

namespace Pcg\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonArrayType;

class JsonType extends JsonArrayType
{
    /**
     * @param array $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'json';
    }

    /**
     * Name of the db type
     *
     * @return string
     */
    public function getName()
    {
        return 'json';
    }

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
