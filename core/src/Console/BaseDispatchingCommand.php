<?php

declare(strict_types=1);

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseDispatchingCommand extends Command
{
    public function __construct(protected EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
    }
}
