<?php

namespace App\Form\Command;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use App\Form\DTO\ChangeLsItemParentDTO;
use Doctrine\Common\Persistence\ObjectManager;

class ChangeLsItemParentCommand
{
    public function convertToDTO(LsItem $lsItem): ChangeLsItemParentDTO
    {
        $dto = new ChangeLsItemParentDTO();
        $dto->lsItem = $lsItem;
        $dto->parentItem = $lsItem->getParentItem();

        return $dto;
    }

    /**
     * @deprecated
     */
    public function perform(ChangeLsItemParentDTO $dto, ObjectManager $manager): LsItem
    {
        $manager->getRepository(LsAssociation::class)->removeAllAssociationsOfType($dto->lsItem, LsAssociation::CHILD_OF);
        $dto->lsItem->addParent($dto->parentItem);

        return $dto->lsItem;
    }
}
