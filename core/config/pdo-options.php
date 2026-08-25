<?php

use Pdo\Mysql;

$pdo_options = null;

$useRdsCert = getenv('DB_USE_RDS_CERT') ?? false;

if (false !== $useRdsCert && '' !== $useRdsCert && '0' !== $useRdsCert) {
    $pdo_options[Mysql::ATTR_SSL_CA] = '%kernel.project_dir%/config/certs/rds-combined-ca-bundle.pem';
} else {
    $pdo_options[Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

$container->setParameter('pdo_options', $pdo_options);
