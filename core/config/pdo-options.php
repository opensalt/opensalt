<?php

$pdo_options = null;

$useRdsCert = getenv('DB_USE_RDS_CERT') ?? false;

if (false !== $useRdsCert && '' !== $useRdsCert && '0' !== $useRdsCert) {
    $pdo_options[PDO::MYSQL_ATTR_SSL_CA] = '%kernel.project_dir%/config/certs/rds-combined-ca-bundle.pem';
}

$container->setParameter('pdo_options', $pdo_options);
