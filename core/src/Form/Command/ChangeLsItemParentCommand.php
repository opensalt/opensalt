<?php

declare(strict_types=1);

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

    #[\Deprecated]
    public function perform(ChangeLsItemParentDTO $dto, LsAssociationRepository $associationRepository): LsItem
    {
        $associationRepository->removeAllAssociationsOfType($dto->lsItem, LsAssociation::CHILD_OF);

        $existingAssocs = $associationRepository->findAllChildAssociationsFor($dto->parentItem->getIdentifier());
        $maxSeq = 0;
        foreach ($existingAssocs as $assoc) {
            $seq = $assoc->getSequenceNumber();
            if (null !== $seq && $seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        $dto->lsItem->addParent($dto->parentItem, $maxSeq + 1);

        return $dto->lsItem;
    }
}
