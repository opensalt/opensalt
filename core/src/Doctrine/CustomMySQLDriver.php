<?php

namespace App\Doctrine;

use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\ServerVersionProvider;

class CustomMySQLDriver extends AbstractDriverMiddleware
{
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractMySQLPlatform
    {
        return new CustomMySQLPlatform();
    }
}
