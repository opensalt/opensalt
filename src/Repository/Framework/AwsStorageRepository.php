<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AwsStorage;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\Framework\LsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Validator\Constraints\DateTime;
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
     * @param string $field
     *
     * @return file
     */
    public function addFile($itemId, string $fileName, string $field)
    {
        $file = new AwsStorage();
        $file->setLsItem($itemId);
        $file->setFileName($fileName);
        $file->setField($field);
        $file->setStatus(true);
        $this->getEntityManager()->persist($file);

        return $file;
    }
        /**
     * @param LsItem $lsitem
     *
     * @return array
     */
    public function findAllItemAttachment(LsItem $lsitem)
    { 
        $qb = $this->createQueryBuilder('i')
                    ->where('i.lsItem = :ls_item_id')            
                    ->setParameter('ls_item_id', $lsitem->getId());
        $result = $qb->getQuery()->getResult();
        return $result;
    }
    
    /**
     * @param LsItem $itemId
     * @param string $fileName
     *
     * @return file
     */

    public function DeleteFile($itemId,$fileName)
    {
       /* $qb = $this->createQueryBuilder('i')
                    ->where('i.fileName = :fileName')            
                    ->setParameter('fileName', $fileName);
        $file = $qb->getQuery()->getResult();
        $file->setStatus(false);
        $file->setDeletedAt(new DateTime());
        $this->getEntityManager()->persist($file);
        return $this;*/
        

        $file = $this->findOneBy(array('fileName' => $fileName));
       
        echo $file->getField();
        $file->setStatus(false);
        $file->setDeletedAt(new \DateTime());
        $this->getEntityManager()->persist($file);
        return $this;
        
    }
}
