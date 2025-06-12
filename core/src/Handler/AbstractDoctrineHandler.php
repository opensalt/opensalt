<?php

declare(strict_types=1);

namespace App\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractDoctrineHandler extends BaseValidatedHandler
{
    public function __construct(ValidatorInterface $validator, protected EntityManagerInterface $em)
    {
        parent::__construct($validator);
    }
}
