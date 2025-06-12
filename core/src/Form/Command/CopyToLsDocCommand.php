<?php

declare(strict_types=1);

namespace App\Form\Command;

use App\Entity\Framework\LsItem;
use App\Form\DTO\CopyToLsDocDTO;
use Doctrine\Persistence\ObjectManager;

class CopyToLsDocCommand
{
    public function convertToDTO(LsItem $lsItem): CopyToLsDocDTO
    {
        $dto = new CopyToLsDocDTO();
        $dto->lsItem = $lsItem;

        return $dto;
    }

    /**
     * @return LsItem
     */
    #[\Deprecated]
    public function perform(CopyToLsDocDTO $dto, ObjectManager $manager)
    {
        $newItem = $dto->lsItem->copyToLsDoc($dto->lsDoc);

        $manager->persist($newItem);

        return $newItem;
    }
}
