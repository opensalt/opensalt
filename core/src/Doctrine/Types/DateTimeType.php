<?php

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 * Class DateTimeType.
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
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        $format = $platform->getDateTimeFormatString();
        if (0 !== (int) $value->format('u')) {
            $format .= '.u';
        }

        return $value->format($format);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTime
    {
        if (null === $value || $value instanceof \DateTime) {
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

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (!in_array($column['precision'] ?? 0, [0, 10], false)) {
            return "DATETIME({$column['precision']})";
        }

        return parent::getSQLDeclaration($column, $platform);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
