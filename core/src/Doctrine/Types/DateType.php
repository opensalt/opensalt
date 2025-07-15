<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;

/**
 * Class DateType.
 *
 * Adds support for DateTimeInterface instead of just DateTime
 */
class DateType extends \Doctrine\DBAL\Types\DateType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($platform->getDateFormatString());
        }

        throw InvalidType::new($value, static::class, ['null', \DateTimeInterface::class]);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTime
    {
        if (null === $value || $value instanceof \DateTime) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTime::createFromInterface($value);
        }

        $dateTime = \DateTime::createFromFormat('!'.$platform->getDateFormatString(), $value);
        if (false !== $dateTime) {
            return $dateTime;
        }

        throw InvalidFormat::new($value, static::class, $platform->getDateFormatString());
    }
}
