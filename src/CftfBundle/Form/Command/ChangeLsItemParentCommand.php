<?php

namespace CftfBundle\Form\Command;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsItem;
use CftfBundle\Form\DTO\ChangeLsItemParentDTO;
use Doctrine\Common\Persistence\ObjectManager;

class ChangeLsItemParentCommand
{
    /**
     * @param \CftfBundle\Entity\LsItem $lsItem
     *
     * @return \CftfBundle\Form\DTO\ChangeLsItemParentDTO
     */
    public function convertToDTO(LsItem $lsItem) {
        $dto = new ChangeLsItemParentDTO();
        $dto->lsItem = $lsItem;
        $dto->parentItem = $lsItem->getParentItem();

        return $dto;
    }

    /**
     * @deprecated
     */
    public function perform(ChangeLsItemParentDTO $dto, ObjectManager $manager) {
        $dto->lsItem->setUpdatedAt(new \DateTime());
        $manager->getRepository(LsAssociation::class)->removeAllAssociationsOfType($dto->lsItem, LsAssociation::CHILD_OF);
        $dto->lsItem->addParent($dto->parentItem);

        return $dto->lsItem;
    }
}
