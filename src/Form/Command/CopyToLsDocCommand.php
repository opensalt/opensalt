<?php

namespace App\Form\Command;

use App\Entity\Framework\LsItem;
use App\Form\DTO\CopyToLsDocDTO;
use Doctrine\Persistence\ObjectManager;

class CopyToLsDocCommand
{
    /**
     * @return CopyToLsDocDTO
     */
    public function convertToDTO(LsItem $lsItem)
    {
        $dto = new CopyToLsDocDTO();
        $dto->lsItem = $lsItem;

        return $dto;
    }

    /**
     * @deprecated
     *
     * @return LsItem
     */
    public function perform(CopyToLsDocDTO $dto, ObjectManager $manager)
    {
        $newItem = $dto->lsItem->copyToLsDoc($dto->lsDoc);

        $manager->persist($newItem);

        return $newItem;
    }
}
