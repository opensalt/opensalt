<?php

namespace App\Repository\Framework;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * LsAssociationRepository
 */
class LsAssociationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LsAssociation::class);
    }

    public function removeAssociation(LsAssociation $lsAssociation): void
    {
        $this->_em->remove($lsAssociation);
        $origin = $lsAssociation->getOrigin();
        if (is_object($origin)) {
            $origin->removeAssociation($lsAssociation);
        }
        $dest = $lsAssociation->getDestination();
        if (is_object($dest)) {
            $dest->removeInverseAssociation($lsAssociation);
        }
    }

    /**
     * Remove all associations of a specific type from the object.
     *
     * @param LsItem|LsDoc $object
     */
    public function removeAllAssociations($object): void
    {
        foreach ($object->getAssociations() as $association) {
            $this->removeAssociation($association);
        }
        foreach ($object->getInverseAssociations() as $association) {
            $this->removeAssociation($association);
        }
    }

    /**
     * Remove all associations of a specific type from the object.
     *
     * @param LsItem|LsDoc $object
     * @param string $type
     *
     * @return LsAssociation[]
     */
    public function removeAllAssociationsOfType($object, $type): array
    {
        $deleted = [];
        foreach ($object->getAssociations() as $association) {
            if ($association->getType() === $type) {
                $this->removeAssociation($association);
                $deleted[] = $association;
            }
        }

        return $deleted;
    }

    public function findAllChildAssociationsFor(string $identifier)
    {
        $qry = $this->createQueryBuilder('a')
            ->where('a.destinationNodeIdentifier = :identifier')
            ->andWhere('a.type = :type')
            ->setParameter('identifier', $identifier)
            ->setParameter('type', LsAssociation::CHILD_OF)
            ->getQuery();

        return $qry->getResult();
    }

    public function findAllAssociationsFor($id)
    {
        $item = $this->getEntityManager()->getRepository(LsItem::class)
            ->findOneBy(['identifier' => str_replace('_', '', $id)]);

        if (null === $item) {
            return null;
        }

        $qry = $this->createQueryBuilder('a')
            ->where('a.originLsItem = :id')
            ->orWhere('a.destinationLsItem = :id')
            ->setParameter('id', $item->getId())
            ->getQuery();

        return $qry->getResult();
    }
}
