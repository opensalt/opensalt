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
        $allTypes = $this->createQueryBuilder('f')
            ->getQuery()
            ->getResult();

        // Deduplicate case-insensitively, keeping smallest ID - O(n) complexity
        $typesByLowerKey = [];
        foreach ($allTypes as $frameworkType) {
            $typeLower = mb_strtolower($frameworkType->getFrameworkType() ?? '');

            // Replace if current ID is smaller (or not yet seen)
            if (!isset($typesByLowerKey[$typeLower])
                || $frameworkType->getId() < $typesByLowerKey[$typeLower]->getId()) {
                $typesByLowerKey[$typeLower] = $frameworkType;
            }
        }

        // Sort alphabetically by framework type name
        $distinctTypes = array_values($typesByLowerKey);
        usort($distinctTypes, fn ($a, $b) => strcasecmp($a->getFrameworkType() ?? '', $b->getFrameworkType() ?? '')
        );

        return $distinctTypes;
    }
}
