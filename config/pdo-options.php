<?php

$pdo_options = null;

if (getenv('DB_USE_RDS_CERT')) {
    $pdo_options[PDO::MYSQL_ATTR_SSL_CA] = '%kernel.project_dir%/config/certs/rds-combined-ca-bundle.pem';
}

$container->setParameter('pdo_options', $pdo_options);
