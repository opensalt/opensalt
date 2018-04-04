<?php

namespace App\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class BaseDoctrineHandler
 */
abstract class BaseDoctrineHandler extends AbstractDoctrineHandler
{
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
        parent::__construct($validator, $registry);
    }
}
