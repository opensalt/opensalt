<?php

namespace CftfBundle\Repository;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityRepository;

/**
 * LsAssociationRepository
 */
class LsAssociationRepository extends EntityRepository
{
    public function removeAssociation(LsAssociation $lsAssociation) {
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
     * Remove all associations of a specific type from the object
     *
     * @param $object LsItem|LsDoc
     */
    public function removeAllAssociations($object) {
        foreach ($object->getAssociations() as $association) {
            $this->removeAssociation($association);
        }
        foreach ($object->getInverseAssociations() as $association) {
            $this->removeAssociation($association);
        }
    }

    /**
     * Remove all associations of a specific type from the object
     *
     * @param $object LsItem|LsDoc
     * @param $type string
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
