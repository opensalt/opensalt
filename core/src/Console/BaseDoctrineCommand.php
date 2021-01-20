<?php

namespace App\Console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseDoctrineCommand extends BaseDispatchingCommand
{
    protected EntityManagerInterface $em;

    public function __construct(EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;

        parent::__construct($dispatcher);
    }
}
