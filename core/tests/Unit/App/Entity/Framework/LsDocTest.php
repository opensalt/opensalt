<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\FrameworkType;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;

class LsDocTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDoc = new LsDoc();

        $this->assertInstanceOf(LsDoc::class, $lsDoc);
        $this->assertNotNull($lsDoc->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDoc->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDoc->getChangedAt());
    }

    public function testGetStatuses()
    {
        $statuses = LsDoc::getStatuses();

        $this->assertIsArray($statuses);
        $this->assertContains(LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT, $statuses);
        $this->assertContains(LsDoc::ADOPTION_STATUS_DRAFT, $statuses);
        $this->assertContains(LsDoc::ADOPTION_STATUS_ADOPTED, $statuses);
        $this->assertContains(LsDoc::ADOPTION_STATUS_DEPRECATED, $statuses);
    }

    public function testGetEditableStatuses()
    {
        $editableStatuses = LsDoc::getEditableStatuses();

        $this->assertIsArray($editableStatuses);
        $this->assertContains(LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT, $editableStatuses);
        $this->assertContains(LsDoc::ADOPTION_STATUS_DRAFT, $editableStatuses);
        $this->assertNotContains(LsDoc::ADOPTION_STATUS_ADOPTED, $editableStatuses);
    }

    public function testIsDraft()
    {
        $lsDoc = new LsDoc();

        // Test with null status (should be draft)
        $this->assertTrue($lsDoc->isDraft());

        // Test with empty string status (should be draft)
        $lsDoc->setAdoptionStatus('');
        $this->assertTrue($lsDoc->isDraft());

        // Test with draft status
        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_DRAFT);
        $this->assertTrue($lsDoc->isDraft());

        // Test with adopted status
        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_ADOPTED);
        $this->assertFalse($lsDoc->isDraft());
    }

    public function testIsAdopted()
    {
        $lsDoc = new LsDoc();

        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_ADOPTED);
        $this->assertTrue($lsDoc->isAdopted());

        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_DRAFT);
        $this->assertFalse($lsDoc->isAdopted());
    }

    public function testIsDeprecated()
    {
        $lsDoc = new LsDoc();

        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_DEPRECATED);
        $this->assertTrue($lsDoc->isDeprecated());

        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_DRAFT);
        $this->assertFalse($lsDoc->isDeprecated());
    }

    public function testSetAndGetTitle()
    {
        $lsDoc = new LsDoc();
        $title = 'Test Framework Title';

        $lsDoc->setTitle($title);
        $this->assertEquals($title, $lsDoc->getTitle());
    }

    public function testGetShortStatement()
    {
        $lsDoc = new LsDoc();

        // Test with null title
        $this->assertEquals('Unknown', $lsDoc->getShortStatement());

        // Test with short title
        $lsDoc->setTitle('Short Title');
        $this->assertEquals('Short Title', $lsDoc->getShortStatement());

        // Test with long title (should be truncated)
        $longTitle = str_repeat('A', 100);
        $lsDoc->setTitle($longTitle);
        $this->assertEquals(60, strlen($lsDoc->getShortStatement()));
    }

    public function testSetAndGetCreator()
    {
        $lsDoc = new LsDoc();
        $creator = 'Test Creator';

        $lsDoc->setCreator($creator);
        $this->assertEquals($creator, $lsDoc->getCreator());
    }

    public function testSetAndGetPublisher()
    {
        $lsDoc = new LsDoc();
        $publisher = 'Test Publisher';

        $lsDoc->setPublisher($publisher);
        $this->assertEquals($publisher, $lsDoc->getPublisher());
    }

    public function testSetAndGetVersion()
    {
        $lsDoc = new LsDoc();
        $version = '1.0.0';

        $lsDoc->setVersion($version);
        $this->assertEquals($version, $lsDoc->getVersion());
    }

    public function testSetAndGetDescription()
    {
        $lsDoc = new LsDoc();
        $description = 'Test Description';

        $lsDoc->setDescription($description);
        $this->assertEquals($description, $lsDoc->getDescription());

        // Test with null description
        $lsDoc->setDescription(null);
        $this->assertNull($lsDoc->getDescription());
    }

    public function testSetAndGetSubject()
    {
        $lsDoc = new LsDoc();
        $subject = ['Mathematics', 'Science'];

        $lsDoc->setSubject($subject);
        $this->assertEquals($subject, $lsDoc->getSubject());

        // Test with string subject
        $lsDoc->setSubject('Mathematics');
        $this->assertEquals(['Mathematics'], $lsDoc->getSubject());

        // Test with null subject - should return null
        $lsDoc->setSubject(null);
        $this->assertNull($lsDoc->getSubject());
    }

    public function testSetAdoptionStatus()
    {
        $lsDoc = new LsDoc();

        // Test setting valid status
        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_ADOPTED);
        $this->assertEquals(LsDoc::ADOPTION_STATUS_ADOPTED, $lsDoc->getAdoptionStatus());

        // Test setting status with different case
        $lsDoc->setAdoptionStatus('draft');
        $this->assertEquals(LsDoc::ADOPTION_STATUS_DRAFT, $lsDoc->getAdoptionStatus());

        // Test setting null status
        $lsDoc->setAdoptionStatus(null);
        $this->assertNull($lsDoc->getAdoptionStatus());
    }

    public function testSetAndGetOfficialUri()
    {
        $lsDoc = new LsDoc();
        $uri = 'https://example.com/framework';

        $lsDoc->setOfficialUri($uri);
        $this->assertEquals($uri, $lsDoc->getOfficialUri());
    }

    public function testSetAndGetLanguage()
    {
        $lsDoc = new LsDoc();
        $language = 'en';

        $lsDoc->setLanguage($language);
        $this->assertEquals($language, $lsDoc->getLanguage());
    }

    public function testSetAndGetUrlName()
    {
        $lsDoc = new LsDoc();
        $urlName = 'test-framework';

        $lsDoc->setUrlName($urlName);
        $this->assertEquals($urlName, $lsDoc->getUrlName());
        $this->assertEquals($urlName, $lsDoc->getSlug());
    }

    public function testGetSlug()
    {
        $lsDoc = new LsDoc();

        // Test with URL name
        $lsDoc->setUrlName('test-framework');
        $this->assertEquals('test-framework', $lsDoc->getSlug());

        // Test without URL name (should return ID)
        $lsDoc->setUrlName(null);
        $this->assertEquals((string) $lsDoc->getId(), $lsDoc->getSlug());
    }

    public function testSetAndGetLicence()
    {
        $lsDoc = new LsDoc();
        $licence = new LsDefLicence();

        $lsDoc->setLicence($licence);
        $this->assertEquals($licence, $lsDoc->getLicence());
    }

    public function testSetAndGetFrameworkType()
    {
        $lsDoc = new LsDoc();
        $frameworkType = new FrameworkType('Standard');

        $lsDoc->setFrameworkType($frameworkType);
        $this->assertEquals($frameworkType, $lsDoc->getFrameworkType());
    }

    public function testCreateItem()
    {
        $lsDoc = new LsDoc();
        $item = $lsDoc->createItem();

        $this->assertInstanceOf(LsItem::class, $item);
        $this->assertEquals($lsDoc, $item->getLsDoc());
    }

    public function testCreateAssociation()
    {
        $lsDoc = new LsDoc();
        $association = $lsDoc->createAssociation();

        $this->assertInstanceOf(LsAssociation::class, $association);
        $this->assertEquals($lsDoc, $association->getLsDoc());
    }

    public function testSetAndGetCaseVersion()
    {
        $lsDoc = new LsDoc();
        $caseVersion = '1.0';

        $lsDoc->setCaseVersion($caseVersion);
        $this->assertEquals($caseVersion, $lsDoc->getCaseVersion());
    }

    public function testPackageExtensions()
    {
        $lsDoc = new LsDoc();
        $extensions = ['key' => 'value'];

        $lsDoc->setPackageExtensions($extensions);
        $this->assertEquals($extensions, $lsDoc->getPackageExtensions());

        // Test with empty array
        $lsDoc->setPackageExtensions([]);
        $this->assertEquals([], $lsDoc->getPackageExtensions());
    }

    public function testDefinitionExtensions()
    {
        $lsDoc = new LsDoc();
        $extensions = ['key' => 'value'];

        $lsDoc->setDefinitionExtensions($extensions);
        $this->assertEquals($extensions, $lsDoc->getDefinitionExtensions());

        // Test with empty array
        $lsDoc->setDefinitionExtensions([]);
        $this->assertEquals([], $lsDoc->getDefinitionExtensions());
    }

    public function testToString()
    {
        $lsDoc = new LsDoc();

        $this->assertEquals($lsDoc->getUri(), (string) $lsDoc);
    }

    public function testCanEdit()
    {
        $lsDoc = new LsDoc();

        // Test with no mirrored framework and null status
        $this->assertTrue($lsDoc->canEdit());

        // Test with draft status
        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_DRAFT);
        $this->assertTrue($lsDoc->canEdit());

        // Test with adopted status
        $lsDoc->setAdoptionStatus(LsDoc::ADOPTION_STATUS_ADOPTED);
        $this->assertFalse($lsDoc->canEdit());
    }

    public function testSetAndGetOrg()
    {
        $lsDoc = new LsDoc();
        $org = new AccessGroup();

        $lsDoc->setOrg($org);
        $this->assertEquals($org, $lsDoc->getOrg());
        $this->assertEquals($org, $lsDoc->getOwner());
    }

    public function testSetAndGetUser()
    {
        $lsDoc = new LsDoc();
        $user = new User();

        $lsDoc->setUser($user);
        $this->assertEquals($user, $lsDoc->getUser());
        $this->assertEquals($user, $lsDoc->getOwner());
    }

    public function testGetOwnedBy()
    {
        $lsDoc = new LsDoc();

        // Test with organization
        $org = new AccessGroup();
        $lsDoc->setOrg($org);
        $this->assertEquals('organization', $lsDoc->getOwnedBy());

        // Test with user
        $user = new User();
        $lsDoc->setUser($user);
        $lsDoc->setOrg(null);
        $this->assertEquals('user', $lsDoc->getOwnedBy());

        // Test with neither
        $lsDoc->setUser(null);
        $this->assertNull($lsDoc->getOwnedBy());
    }

    public function testSetOwnedBy()
    {
        $lsDoc = new LsDoc();

        $lsDoc->setOwnedBy('organization');
        $this->assertEquals('organization', $lsDoc->getOwnedBy());

        $lsDoc->setOwnedBy('user');
        $this->assertEquals('user', $lsDoc->getOwnedBy());

        $lsDoc->setOwnedBy(null);
        $this->assertNull($lsDoc->getOwnedBy());
    }

    public function testSetOwnedByInvalidValue()
    {
        $lsDoc = new LsDoc();

        $this->expectException(\InvalidArgumentException::class);
        $lsDoc->setOwnedBy('invalid');
    }
}
