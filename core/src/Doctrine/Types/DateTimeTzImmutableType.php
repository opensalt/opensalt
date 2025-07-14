<?php

declare(strict_types=1);

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
class DateTimeTzImmutableType extends \Doctrine\DBAL\Types\DateTimeTzImmutableType
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
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
    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        if (null === $value || $value instanceof \DateTimeImmutable) {
            return $value;
        }

        $format = $platform->getDateTimeFormatString();
        if (preg_match('/\.\d+$/', $value)) {
            $format .= '.u';
        }

        $val = \DateTimeImmutable::createFromFormat($format, $value);
        if (!$val) {
            throw new ConversionException(sprintf('Could not convert %s to a DateTime (expecting format %s)', $value, $format));
        }

        return $val;
    }

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (!in_array($column['precision'] ?? 0, [0, 10], false)) {
            return sprintf('DATETIME(%s)', $column['precision']);
        }

        return parent::getSQLDeclaration($column, $platform);
    }
}
