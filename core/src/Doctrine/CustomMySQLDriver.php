<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\ServerVersionProvider;

class CustomMySQLDriver extends AbstractDriverMiddleware
{
    #[\Override]
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractMySQLPlatform
    {
        return new CustomMySQLPlatform();
    }
}
