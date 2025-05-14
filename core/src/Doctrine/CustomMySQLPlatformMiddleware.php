<?php

namespace App\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

class CustomMySQLPlatformMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new CustomMySQLDriver($driver);
    }
}
