<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\ObjectLock;
use App\Entity\User\User;

class ObjectLockTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    /**
     * Helper method to set ID on LsDoc using reflection
     */
    private function setLsDocId(LsDoc $lsDoc, int $id): void
    {
        $reflection = new \ReflectionClass($lsDoc);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($lsDoc, $id);
    }

    // tests
    public function testConstructorWithLsDoc()
    {
        $lsDoc = new LsDoc();
        // Use reflection to set the ID since LsDoc doesn't have a public setId method
        $reflection = new \ReflectionClass($lsDoc);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($lsDoc, 123);

        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user);

        $this->assertInstanceOf(ObjectLock::class, $objectLock);
        $this->assertEquals($user, $objectLock->getUser());
        $this->assertEquals(LsDoc::class, $objectLock->getObjectType());
        $this->assertEquals('123', $objectLock->getObjectId());
    }

    public function testConstructorWithLsDocAndCustomMinutes()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 456);
        $user = new User();
        $minutes = 10;

        $objectLock = new ObjectLock($lsDoc, $user, $minutes);

        $this->assertInstanceOf(ObjectLock::class, $objectLock);
        $this->assertEquals($user, $objectLock->getUser());
        $this->assertEquals(LsDoc::class, $objectLock->getObjectType());
        $this->assertEquals('456', $objectLock->getObjectId());
    }

    public function testConstructorThrowsExceptionForNullId()
    {
        $lsDoc = new LsDoc();
        // Don't set ID - should be null
        $user = new User();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Attempt to lock non-persisted object.');

        new ObjectLock($lsDoc, $user);
    }

    public function testGetId()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 789);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user);

        $this->assertNull($objectLock->getId());
    }

    public function testGetUser()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 111);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user);

        $this->assertEquals($user, $objectLock->getUser());
    }

    public function testIsExpired()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 222);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user, 1); // 1 minute timeout

        // Should not be expired immediately
        $this->assertFalse($objectLock->isExpired());
    }

    public function testGetTimeout()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 333);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user, 5);

        $timeout = $objectLock->getTimeout();
        $this->assertInstanceOf(\DateTimeInterface::class, $timeout);

        // Timeout should be in the future
        $this->assertGreaterThan(new \DateTime(), $timeout);
    }

    public function testAddTime()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 444);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user, 5);
        $originalTimeout = $objectLock->getTimeout();

        // Add 10 minutes
        $objectLock->addTime(10);
        $newTimeout = $objectLock->getTimeout();

        $this->assertGreaterThan($originalTimeout, $newTimeout);
    }

    public function testGetObjectType()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 555);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user);

        $this->assertEquals(LsDoc::class, $objectLock->getObjectType());
    }

    public function testGetObjectId()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 666);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user);

        $this->assertEquals('666', $objectLock->getObjectId());
    }

    public function testDocProperty()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 777);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user);

        $this->assertEquals($lsDoc->getId(), $objectLock->getObjectId());
    }

    public function testDifferentObjectIds()
    {
        $user = new User();

        $lsDoc1 = new LsDoc();
        $this->setLsDocId($lsDoc1, 111);
        $lock1 = new ObjectLock($lsDoc1, $user);

        $lsDoc2 = new LsDoc();
        $this->setLsDocId($lsDoc2, 222);
        $lock2 = new ObjectLock($lsDoc2, $user);

        $this->assertEquals(111, $lock1->getObjectId());
        $this->assertEquals(222, $lock2->getObjectId());
        $this->assertNotEquals($lock1->getObjectId(), $lock2->getObjectId());
    }

    public function testTimeoutCalculation()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 888);
        $user = new User();

        $beforeCreation = new \DateTime();
        $objectLock = new ObjectLock($lsDoc, $user, 15);
        $afterCreation = new \DateTime();

        $timeout = $objectLock->getTimeout();

        // Timeout should be at least 15 minutes from before creation
        $expectedTimeout = clone $beforeCreation;
        $expectedTimeout->add(new \DateInterval('PT15M'));

        $this->assertGreaterThanOrEqual($expectedTimeout, $timeout);

        // Timeout should be at most 15 minutes from after creation
        $maxExpectedTimeout = clone $afterCreation;
        $maxExpectedTimeout->add(new \DateInterval('PT15M'));

        $this->assertLessThanOrEqual($maxExpectedTimeout, $timeout);
    }

    public function testZeroMinutes()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 999);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user, 0);

        $timeout = $objectLock->getTimeout();
        $now = new \DateTime();

        // Should expire immediately (within a few seconds)
        $this->assertLessThanOrEqual(5, $timeout->getTimestamp() - $now->getTimestamp());
    }

    public function testLargeMinutesValue()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 1000);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user, 1440); // 24 hours

        $timeout = $objectLock->getTimeout();
        $now = new \DateTime();

        // Should be approximately 24 hours from now
        $expectedSeconds = 1440 * 60; // 24 hours in seconds
        $actualSeconds = $timeout->getTimestamp() - $now->getTimestamp();

        $this->assertGreaterThanOrEqual($expectedSeconds - 10, $actualSeconds);
        $this->assertLessThanOrEqual($expectedSeconds + 10, $actualSeconds);
    }

    public function testAddTimeMultipleTimes()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 1100);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user, 5);
        $timeout1 = $objectLock->getTimeout();

        $objectLock->addTime(5);
        $timeout2 = $objectLock->getTimeout();

        $objectLock->addTime(10);
        $timeout3 = $objectLock->getTimeout();

        $this->assertGreaterThan($timeout1, $timeout2);
        $this->assertGreaterThan($timeout2, $timeout3);
    }

    public function testConstructorWithDifferentUsers()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 1200);

        $user1 = new User();
        $user2 = new User();

        $lock1 = new ObjectLock($lsDoc, $user1);
        $lock2 = new ObjectLock($lsDoc, $user2);

        $this->assertEquals($user1, $lock1->getUser());
        $this->assertEquals($user2, $lock2->getUser());
        $this->assertNotSame($lock1->getUser(), $lock2->getUser());
    }

    public function testObjectTypeConsistency()
    {
        $lsDoc = new LsDoc();
        $this->setLsDocId($lsDoc, 1300);
        $user = new User();

        $objectLock = new ObjectLock($lsDoc, $user);

        $this->assertEquals($objectLock->getObjectType(), LsDoc::class);
        $this->assertEquals($objectLock->getObjectType(), get_class($lsDoc));
    }
}
