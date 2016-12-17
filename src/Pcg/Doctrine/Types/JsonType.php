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
     * Convert the value of the field into a PHP data type
     *
     * @param mixed $value
     * @param AbstractPlatform $platform
     *
     * @return array|mixed|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value === null ? null : parent::convertToPHPValue($value, $platform);
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
}
