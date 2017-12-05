<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\EntityManager;
use CftfBundle\Entity\LsItem;
use Ramsey\Uuid\Uuid;

class LsItemTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testIncreasedAbbreviatedStatement()
    {
        $lsItem = new LsItem();
        $em = $this->getModule('Doctrine2')->em;

        $identifier = Uuid::uuid4()->toString();
        $docIdentifier = Uuid::uuid4()->toString();
        $lsItem->setIdentifier($identifier);
        $lsItem->setLsDocIdentifier($docIdentifier);
        $lsItem->setAbbreviatedStatement('012345678901234567890123456789012345678901234567890123456789');
        $lsItem->setFullStatement('full statement');

        $em->persist($lsItem);
        $em->flush();

        $this->tester->seeInRepository(LsItem::class, ['identifier' => $identifier]);
        $em->clear();

        $item = $em->getRepository(LsItem::class)->findOneBy(['identifier' => $identifier]);
        $this->assertEquals($item->getAbbreviatedStatement(), '012345678901234567890123456789012345678901234567890123456789');
    }
}
