<?php

namespace App\Form\Command;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use App\Form\DTO\ChangeLsItemParentDTO;
use App\Repository\Framework\LsAssociationRepository;

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
    public function perform(ChangeLsItemParentDTO $dto, LsAssociationRepository $associationRepository): LsItem
    {
        $associationRepository->removeAllAssociationsOfType($dto->lsItem, LsAssociation::CHILD_OF);
        $dto->lsItem->addParent($dto->parentItem);

        return $dto->lsItem;
    }
}
