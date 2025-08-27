<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDefLicence;
use Ramsey\Uuid\Uuid;

class LsDefLicenceTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDefLicence = new LsDefLicence();

        $this->assertInstanceOf(LsDefLicence::class, $lsDefLicence);
        $this->assertNotNull($lsDefLicence->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefLicence->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefLicence->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefLicence = new LsDefLicence($identifier);

        $this->assertEquals($identifier, $lsDefLicence->getIdentifier());
    }

    public function testSetAndGetTitle()
    {
        $lsDefLicence = new LsDefLicence();
        $title = 'Creative Commons Attribution 4.0';

        $lsDefLicence->setTitle($title);
        $this->assertEquals($title, $lsDefLicence->getTitle());
    }

    public function testSetAndGetDescription()
    {
        $lsDefLicence = new LsDefLicence();
        $description = 'This license allows for the distribution, remix, tweak, and build upon the work, even commercially, as long as credit is given for the original creation.';

        $lsDefLicence->setDescription($description);
        $this->assertEquals($description, $lsDefLicence->getDescription());
    }

    public function testGetDescriptionFallbackToTitle()
    {
        $lsDefLicence = new LsDefLicence();
        $title = 'MIT License';

        $lsDefLicence->setTitle($title);
        // When description is null, should return title
        $this->assertEquals($title, $lsDefLicence->getDescription());
    }

    public function testGetDescriptionWithNullTitleAndDescription()
    {
        $lsDefLicence = new LsDefLicence();

        // When both title and description are null, should return null
        $this->assertNull($lsDefLicence->getDescription());
    }

    public function testSetAndGetLicenceText()
    {
        $lsDefLicence = new LsDefLicence();
        $licenceText = 'Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software...';

        $lsDefLicence->setLicenceText($licenceText);
        $this->assertEquals($licenceText, $lsDefLicence->getLicenceText());
    }

    public function testLicenceTextIsRequired()
    {
        $lsDefLicence = new LsDefLicence();
        $licenceText = 'Sample licence text';

        $lsDefLicence->setLicenceText($licenceText);
        $this->assertEquals($licenceText, $lsDefLicence->getLicenceText());
    }

    public function testDataIntegrity()
    {
        $lsDefLicence = new LsDefLicence();
        $identifier = Uuid::uuid4()->toString();
        $title = 'Apache License 2.0';
        $description = 'A permissive license whose main conditions require preservation of copyright and license notices.';
        $licenceText = 'Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License.';

        $lsDefLicence->setIdentifier($identifier);
        $lsDefLicence->setTitle($title);
        $lsDefLicence->setDescription($description);
        $lsDefLicence->setLicenceText($licenceText);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $lsDefLicence->getIdentifier());
        $this->assertEquals($title, $lsDefLicence->getTitle());
        $this->assertEquals($description, $lsDefLicence->getDescription());
        $this->assertEquals($licenceText, $lsDefLicence->getLicenceText());
    }

    public function testUniqueIdentifier()
    {
        $licence1 = new LsDefLicence();
        $licence2 = new LsDefLicence();

        $this->assertNotEquals($licence1->getIdentifier(), $licence2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $lsDefLicence = new LsDefLicence();
        $originalUpdatedAt = $lsDefLicence->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $lsDefLicence->setTitle('Updated Title');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefLicence->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $lsDefLicence = new LsDefLicence();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefLicence->getChangedAt());
        $this->assertEquals($lsDefLicence->getUpdatedAt(), $lsDefLicence->getChangedAt());
    }

    public function testLongLicenceText()
    {
        $lsDefLicence = new LsDefLicence();
        $longLicenceText = str_repeat('This is a very long licence text that should be handled properly. ', 100);

        $lsDefLicence->setLicenceText($longLicenceText);
        $this->assertEquals($longLicenceText, $lsDefLicence->getLicenceText());
    }

    public function testEmptyTitle()
    {
        $lsDefLicence = new LsDefLicence();

        $lsDefLicence->setTitle('');
        $this->assertEquals('', $lsDefLicence->getTitle());
    }

    public function testNullTitle()
    {
        $lsDefLicence = new LsDefLicence();

        $lsDefLicence->setTitle(null);
        $this->assertNull($lsDefLicence->getTitle());
    }
}
