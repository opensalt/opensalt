<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class LsItemTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testIncreasedAbbreviatedStatement()
    {
        $lsItem = new LsItem();
        /** @var EntityManagerInterface $em */
        $em = $this->getModule('Doctrine')->em;

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
