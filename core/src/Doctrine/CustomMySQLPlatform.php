<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Schema\MySQLSchemaManager;

class CustomMySQLPlatform extends MySQL84Platform
{
    #[\Override]
    public function createSchemaManager(Connection $connection): MySQLSchemaManager
    {
        return new CustomMySQLSchemaManager($connection, $this);
    }
}
