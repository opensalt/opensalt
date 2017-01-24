<?php

namespace CftfBundle\Form\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsItem;
use CftfBundle\Form\DTO\CopyToLsDocDTO;
use Doctrine\Common\Persistence\ObjectManager;

class CopyToLsDocCommand
{
    public function convertToDTO(LsItem $lsItem) {
        $dto = new CopyToLsDocDTO();
        $dto->lsItem = $lsItem;

        return $dto;
    }

    public function perform(CopyToLsDocDTO $dto, ObjectManager $manager) {
        $newItem = $dto->lsItem->copyToLsDoc($dto->lsDoc);

        $manager->persist($newItem);

        return $newItem;
    }
}
