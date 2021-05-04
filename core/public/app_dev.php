<?php

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (empty($_COOKIE['dev'] ?? null)
        || empty($_SERVER['DEV_COOKIE'] ?? null)
        || $_COOKIE['dev'] !== $_SERVER['DEV_COOKIE']
    ) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file.');
}

$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = '1';

require __DIR__.'/index.php';
