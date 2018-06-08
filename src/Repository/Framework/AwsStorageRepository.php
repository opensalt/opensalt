<?php

namespace App\Repository\Framework;

use App\Entity\Framework\AwsStorage;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\Framework\LsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;

/**
 * AwsStorageRepository.
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
                    ->where('i.lsItem = :ls_item_id and i.status=1 ')
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
    public function deleteFile($itemId, $fileName)
    {
        $file = $this->findOneBy(['fileName' => $fileName]);
        $file->setStatus(false);
        $file->setDeletedAt(new \DateTime());
        $this->getEntityManager()->persist($file);

        return $this;
    }

    /**
     * @param LsItem $itemId
     *
     * @return array
     */
    public function findItemAttachmenById($itemId, $format = Query::HYDRATE_ARRAY)
    {
        $qb = $this->createQueryBuilder('i')
                   ->where('i.lsItem = :ls_item_id and i.status=1 ')
                   ->setParameter('ls_item_id', $itemId);

        $result = $qb->getQuery()->getResult($format);

        return $result;
    }

    /**
     * @param LsItem $itemId
     * @param string $fileList
     *
     * @return file
     */
    public function updateFile($lsitemId, $fileList)
    {
        foreach ($fileList as $fileName) {
            $file = $this->findOneBy(['fileName' => $fileName]);
            $file->setLsItem($lsitemId);
            $file->setStatus(true);
            $this->getEntityManager()->persist($file);
        }

        return $this;
    }
}
