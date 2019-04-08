<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsDefLicence;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LsDefLicence|null findOneByIdentifier(string $identifier)
 */
class LsDefLicenceRepository extends AbstractLsDefinitionRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsDefLicence::class);
    }

    /**
     * @return array|LsDefLicence[]|ArrayCollection
     */
    public function getList()
    {
        $qBuilder = $this->createQueryBuilder('s', 's.title')
            ->orderBy('s.title');

        return $qBuilder->getQuery()->getResult();
    }
}
