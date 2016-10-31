<?php
/**
 *
 */

namespace CftfBundle\Form\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsItem;
use CftfBundle\Form\DTO\CopyToLsDocDTO;
use Doctrine\Common\Persistence\ObjectManager;

class CopyToLsDocCommand {
    public function convertToDTO(LsItem $lsItem) {
        $dto = new CopyToLsDocDTO();
        $dto->lsItem = $lsItem;

        return $dto;
    }

    public function perform(CopyToLsDocDTO $dto, ObjectManager $manager) {
        $newItem = clone $dto->lsItem;
        $newItem->setLsDoc($dto->lsDoc);
        $dto->lsDoc->addTopLsItem($newItem);

        $association = new LsAssociation();
        $association->setLsDoc($dto->lsDoc);
        $association->setOrigin($newItem);
        $association->setDestination($dto->lsItem);
        $association->setType(LsAssociation::EXACT_MATCH_OF);

        $manager->persist($newItem);
        $manager->persist($association);

        return $newItem;
    }
}