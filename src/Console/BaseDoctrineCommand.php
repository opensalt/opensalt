<?php

namespace App\Console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseDoctrineCommand extends BaseDispatchingCommand
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;

        parent::__construct($dispatcher);
    }
}
