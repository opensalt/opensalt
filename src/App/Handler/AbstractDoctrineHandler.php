<?php

namespace App\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AbstractDoctrineHandler
 */
abstract class AbstractDoctrineHandler extends BaseValidatedHandler
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * AbstractUserHandler constructor.
     *
     * @param ValidatorInterface $validator
     * @param ManagerRegistry $registry
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        parent::__construct($validator);
        $this->em = $registry->getManager();
    }
}
