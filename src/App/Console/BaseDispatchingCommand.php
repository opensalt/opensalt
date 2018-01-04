<?php

namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseDispatchingCommand extends Command
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        parent::__construct();
    }
}
