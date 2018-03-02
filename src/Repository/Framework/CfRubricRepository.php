<?php

namespace App\Repository\Framework;

use App\Entity\Framework\CfRubric;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CfRubricRepository extends AbstractLsBaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CfRubric::class);
    }

}
