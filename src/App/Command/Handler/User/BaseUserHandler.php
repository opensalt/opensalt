<?php

namespace App\Command\Handler\User;

use App\Command\Handler\BaseValidatedHandler;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class BaseUserHandler
 */
abstract class BaseUserHandler extends BaseValidatedHandler
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * BaseUserHandler constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "registry" = @DI\Inject("doctrine")
     * })
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
