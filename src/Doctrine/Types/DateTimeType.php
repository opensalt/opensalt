<?php

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 * Class DateTimeType
 *
 * Adds support for datetime(3) in MySQL
 *
 * @see https://gist.github.com/coudenysj/6dc8ba55c43b97143a6c
 */
class DateTimeType extends \Doctrine\DBAL\Types\DateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $format = $platform->getDateTimeFormatString();
        if ((int) $value->format('u') !== 0) {
            $format .= '.u';
        }

        return $value->format($format);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof \DateTime) {
            return $value;
        }

        $format = $platform->getDateTimeFormatString();
        if (preg_match('/\.\d+$/', $value)) {
            $format .= '.u';
        }

        $val = \DateTime::createFromFormat($format, $value);
        if (!$val) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), $platform->getDateTimeFormatString().'.u');
        }

        return $val;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if (!in_array($fieldDeclaration['precision'] ?? 0, [0, 10])) {
            return "DATETIME({$fieldDeclaration['precision']})";
        }

        return parent::getSQLDeclaration($fieldDeclaration, $platform);
    }
}
