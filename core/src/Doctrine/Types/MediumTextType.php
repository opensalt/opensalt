<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\TextType;

class MediumTextType extends TextType
{
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return 'MEDIUMTEXT';
        }

        return parent::getSQLDeclaration($column, $platform);
    }

    public function getName(): string
    {
        return 'medium_text';
    }

    #[\Override]
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        if ($platform instanceof MySQLPlatform) {
            return ['mediumtext'];
        }

        return parent::getMappedDatabaseTypes($platform);
    }
}
