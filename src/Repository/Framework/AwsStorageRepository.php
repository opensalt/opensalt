<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AwsStorage;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\Query;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * AwsStorageRepository
 */
class AwsStorageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AwsStorage::class);
    }

    /**
     * @param LsItem $itemId
     * @param string $fileName
     *
     * @return file
     */
    public function addFile($itemId, string $fileName)
    {
        $file = new AwsStorage();
        $file->setLsItem($itemId);
        $file->setFileName($fileName);
        $this->getEntityManager()->persist($file);

        return $file;
    }

}
