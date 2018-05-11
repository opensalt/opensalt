<?php

namespace App\Entity\Framework;

use Ramsey\Uuid\Uuid;
use App\Entity\Framework\AwsStorage;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;

class AwsStorageAttachmentTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testAddAttachment()
    { 
        /** @var EntityManagerInterface $em */
        $em = $this->getModule('Doctrine2')->em;
        $fileName = "test.txt";
        $itemId = $this->addLsItem();
        $item = $em->getRepository(LsItem::class)->find($itemId);
        $field = 'fullStatement';
        $file = new AwsStorage();

        $file->setLsItem($item);
        $file->setFileName($fileName);
        $file->setField($field);
        $file->setStatus(true);
        $em->persist($file);
        $em->flush();

        $this->tester->seeInRepository(AwsStorage::class, ['lsItem' => $itemId]);
        $em->clear();

        $item = $em->getRepository(AwsStorage::class)->findOneBy(['lsItem' => $itemId]);
        $this->assertEquals($item->getFileName(), $fileName);
    }

     public function addLsItem()
    {
        $identifier = Uuid::uuid4()->toString();
        $docIdentifier = Uuid::uuid4()->toString();
        $lsItemId = $this->tester->haveInRepository(LsItem::class,
            [
                'identifier' => $identifier,
                'lsDocIdentifier' => $docIdentifier,
                'fullStatement' => 'codeception'
            ]
        );

        return $lsItemId;
    }
}
