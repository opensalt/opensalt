<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\MySQLSchemaManager;

class CustomMySQLPlatform extends MySQLPlatform
{
    #[\Override]
    public function createSchemaManager(Connection $connection): MySQLSchemaManager
    {
        return new CustomMySQLSchemaManager($connection, $this);
    }
}
