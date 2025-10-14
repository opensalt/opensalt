<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\FrameworkType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FrameworkType>
 */
class FrameworkTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FrameworkType::class);
    }

    /**
     * @return FrameworkType[]
     */
    public function getList(): array
    {
        $qBuilder = $this->createQueryBuilder('f', 'f.frameworkType')
            ->orderBy('f.frameworkType');

        return $qBuilder->getQuery()->getResult();
    }
}
