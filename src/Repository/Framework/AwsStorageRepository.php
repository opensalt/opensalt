<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AwsStorage;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ImportLogRepository
 */
class AwsStorageRepository extends EntityRepository
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
        $file->setLsItem($lsItem);
        $file->setFileName($fileName);
        
        $this->getEntityManager()->persist($file);

        return $file;
    }

}