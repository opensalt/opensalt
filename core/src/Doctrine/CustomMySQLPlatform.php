<?php

namespace App\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Schema\MySQLSchemaManager;

class CustomMySQLPlatform extends MySQL84Platform
{
    public function createSchemaManager(Connection $connection): MySQLSchemaManager
    {
        return new CustomMySQLSchemaManager($connection, $this);
    }
}
