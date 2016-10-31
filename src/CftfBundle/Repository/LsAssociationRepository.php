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
     */
    public function removeAllAssociationsOfType($object, $type) {
        foreach ($object->getAssociations() as $association) {
            if ($association->getType() === $type) {
                $this->removeAssociation($association);
            }
        }
    }
}
