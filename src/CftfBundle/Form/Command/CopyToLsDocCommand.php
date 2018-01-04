<?php

namespace CftfBundle\Form\Command;

use CftfBundle\Entity\LsItem;
use CftfBundle\Form\DTO\CopyToLsDocDTO;
use Doctrine\Common\Persistence\ObjectManager;

class CopyToLsDocCommand
{
    /**
     * @param LsItem $lsItem
     *
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
     * @param CopyToLsDocDTO $dto
     * @param ObjectManager $manager
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
