<?php

use App\Kernel;

require __DIR__.'/../vendor/autoload.php';

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env));

$kernel = new Kernel($env, $debug);
$kernel->boot();
return $kernel->getContainer()->get('doctrine')->getManager();
