<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefFrameworkType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LsDefFrameworkType|null find($id, $lockMode = null, $lockVersion = null)
 * @method LsDefFrameworkType|null findOneBy(array $criteria, array $orderBy = null)
 * @method LsDefFrameworkType[]    findAll()
 * @method LsDefFrameworkType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LsDefFrameworkTypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefFrameworkType::class);
    }

    /**
     * @return array|LsDefFrameworkType[]
     */
    public function getList()
    {
        $qBuilder = $this->createQueryBuilder('f', 'f.value')
            ->orderBy('f.value');

        return $qBuilder->getQuery()->getResult();
    }
}
