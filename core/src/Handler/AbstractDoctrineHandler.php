<?php

namespace App\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AbstractDoctrineHandler
 */
abstract class AbstractDoctrineHandler extends BaseValidatedHandler
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        parent::__construct($validator);
        $this->em = $entityManager;
    }
}
