<?php

namespace App\Console;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseDoctrineCommand extends BaseDispatchingCommand
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EventDispatcherInterface $dispatcher, ManagerRegistry $registry)
    {
        $this->em = $registry->getManager();

        parent::__construct($dispatcher);
    }
}
