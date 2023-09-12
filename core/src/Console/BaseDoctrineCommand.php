<?php

namespace App\Console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseDoctrineCommand extends BaseDispatchingCommand
{
    public function __construct(EventDispatcherInterface $dispatcher, protected EntityManagerInterface $em)
    {
        parent::__construct($dispatcher);
    }
}
