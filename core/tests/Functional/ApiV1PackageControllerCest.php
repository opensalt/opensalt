<?php

namespace Tests\Functional;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\ApiToken;
use App\Entity\User\User;
use Codeception\Util\HttpCode;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class ApiV1PackageControllerCest
{
    private string $apiToken;
    private User $testUser;
    private LsDoc $testDoc;

    public function _before(FunctionalTester $I): void
    {
        // Create a test user with appropriate permissions
        $I->ensureUserExistsWithRole('SUPER_EDITOR');
        $this->testUser = $I->getLastUser();

        // Create API token for the user
        $this->apiToken = $this->createApiToken($I, $this->testUser);

        // Create test document with components
        $this->testDoc = $this->createTestPackage($I);
    }

    private function createApiToken(FunctionalTester $I, User $user): string
    {
        $tokenEntity = ApiToken::createToken($user, 'Test Token', null);
        $em = $I->grabService('doctrine.orm.entity_manager');
        $em->persist($tokenEntity);
        $em->flush();

        return $tokenEntity->token;
    }

    private function createTestPackage(FunctionalTester $I): LsDoc
    {
        $docIdentifier = Uuid::uuid4()->toString();
        $I->haveInRepository(LsDoc::class, [
            'identifier' => $docIdentifier,
            'title' => 'Test Package',
            'creator' => 'Test Creator',
            'version' => '1.0',
            'description' => 'Test package for API testing',
            'adoptionStatus' => 'Draft',
            'statusStart' => new \DateTimeImmutable(),
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        $doc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => $docIdentifier]);

        // Create test item
        $itemIdentifier = Uuid::uuid4()->toString();
        $I->haveInRepository(LsItem::class, [
            'identifier' => $itemIdentifier,
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'fullStatement' => 'Test Item Statement',
            'humanCodingScheme' => 'Test Code',
            'listEnumInSource' => '1',
            'abbreviatedStatement' => 'Test Item',
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        // Create test association
        $item = $I->grabEntityFromRepository(LsItem::class, ['identifier' => $itemIdentifier]);
        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'originLsItem' => $item,
            'originNodeIdentifier' => $item->getIdentifier(),
            'destinationLsDoc' => $doc,
            'destinationNodeIdentifier' => $doc->getIdentifier(),
            'type' => 'isChildOf',
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        // Create test rubric
        $rubricIdentifier = Uuid::uuid4()->toString();
        $I->haveInRepository(CfRubric::class, [
            'identifier' => $rubricIdentifier,
            'title' => 'Test Rubric',
            'description' => 'Test rubric description',
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        return $doc;
    }

    // Test GET /api/v1/packages (index)
    public function testIndexPackages(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/api/v1/packages');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.data');
        $I->seeResponseJsonMatchesJsonPath('$.pagination');
    }

    public function testIndexPackagesWithPagination(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages?page[size]=5');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    public function testIndexPackagesWithFilters(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages?filter[title]=Test&filter[creator]=Test Creator');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    // Test GET /api/v1/packages/{documentIdentifier}
    public function testGetPackage(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        // Check package structure
        $I->seeResponseJsonMatchesJsonPath('$.CFDocument');
        $I->seeResponseJsonMatchesJsonPath('$.CFItems');
        $I->seeResponseJsonMatchesJsonPath('$.CFAssociations');
        $I->seeResponseJsonMatchesJsonPath('$.CFDefinitions');
        $I->seeResponseJsonMatchesJsonPath('$.CFRubrics');

        // Check document data
        $I->seeResponseContainsJson([
            'CFDocument' => [
                'title' => 'Test Package',
                'creator' => 'Test Creator',
            ],
        ]);
    }

    public function testGetPackageNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages/non-existent-doc');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    // Test POST /api/v1/packages
    public function testPostPackage(FunctionalTester $I): void
    {
        $packageData = [
            'CFDocument' => [
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/document',
                'title' => 'New Test Package',
                'creator' => 'Test Creator',
                'version' => '1.0',
                'description' => 'New test package',
                'adoptionStatus' => 'Draft',
                'caseVersion' => '1.1',
            ],
            'CFItems' => [
                [
                    'identifier' => Uuid::uuid4()->toString(),
                    'uri' => 'http://example.com/item',
                    'fullStatement' => 'New Item Statement',
                    'humanCodingScheme' => 'NEW001',
                    'listEnumInSource' => '1',
                    'abbreviatedStatement' => 'New Item',
                ],
            ],
            'CFAssociations' => [
                [
                    'identifier' => Uuid::uuid4()->toString(),
                    'uri' => 'http://example.com/association',
                    'originNodeURI' => [
                        'identifier' => Uuid::uuid4()->toString(),
                        'uri' => 'http://example.com/origin',
                        'title' => 'Origin Item',
                    ],
                    'associationType' => 'isChildOf',
                    'destinationNodeURI' => [
                        'identifier' => Uuid::uuid4()->toString(),
                        'uri' => 'http://example.com/destination',
                        'title' => 'Destination Item',
                    ],
                ],
            ],
            'CFDefinitions' => [
                'CFConcepts' => [],
                'CFSubjects' => [],
                'CFLicenses' => [],
                'CFItemTypes' => [],
                'CFAssociationGroupings' => [],
            ],
            'CFRubrics' => [
                [
                    'identifier' => Uuid::uuid4()->toString(),
                    'uri' => 'http://example.com/rubric',
                    'title' => 'New Rubric',
                    'description' => 'New rubric description',
                ],
            ],
        ];

        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/api/v1/packages', $packageData);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        // Check response structure
        $I->seeResponseJsonMatchesJsonPath('$.CFDocument');
        $I->seeResponseJsonMatchesJsonPath('$.CFItems');
        $I->seeResponseJsonMatchesJsonPath('$.CFAssociations');
        $I->seeResponseJsonMatchesJsonPath('$.CFDefinitions');
        $I->seeResponseJsonMatchesJsonPath('$.CFRubrics');

        $I->seeResponseContainsJson([
            'CFDocument' => [
                'title' => 'New Test Package',
            ],
        ]);
    }

    public function testPostPackageMinimal(FunctionalTester $I): void
    {
        $packageData = [
            'CFDocument' => [
                'identifier' => Uuid::uuid4()->toString(),
                'title' => 'Minimal Package',
                'creator' => 'Test Creator',
            ],
        ];

        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/api/v1/packages', $packageData);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
    }

    public function testPostPackageValidationError(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/api/v1/packages', [
            // Missing required CFDocument
            'CFItems' => [],
        ]);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function testPostPackageUnauthorized(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/api/v1/packages', [
            'CFDocument' => [
                'identifier' => Uuid::uuid4()->toString(),
                'title' => 'New Test Package',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test PUT /api/v1/packages/{documentIdentifier}
    public function testPutPackage(FunctionalTester $I): void
    {
        $packageData = [
            'CFDocument' => [
                'identifier' => $this->testDoc->getIdentifier(),
                'uri' => 'http://example.com/document',
                'title' => 'Updated Test Package',
                'creator' => 'Updated Creator',
                'version' => '1.1',
                'description' => 'Updated package description',
            ],
            'CFItems' => [
                [
                    'identifier' => Uuid::uuid4()->toString(),
                    'uri' => 'http://example.com/item',
                    'fullStatement' => 'Updated Item Statement',
                    'humanCodingScheme' => 'UPD001',
                    'listEnumInSource' => '1',
                    'abbreviatedStatement' => 'Updated Item',
                ],
            ],
            'CFAssociations' => [],
            'CFDefinitions' => [
                'CFConcepts' => [],
                'CFSubjects' => [],
                'CFLicenses' => [],
                'CFItemTypes' => [],
                'CFAssociationGroupings' => [],
            ],
            'CFRubrics' => [
                [
                    'identifier' => Uuid::uuid4()->toString(),
                    'uri' => 'http://example.com/rubric',
                    'title' => 'Updated Rubric',
                    'description' => 'Updated rubric description',
                ],
            ],
        ];

        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier(), $packageData);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'CFDocument' => [
                'title' => 'Updated Test Package',
            ],
        ]);
    }

    public function testPutPackageNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/api/v1/packages/non-existent-doc', [
            'CFDocument' => [
                'title' => 'Updated Title',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testPutPackageUnauthorized(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier(), [
            'CFDocument' => [
                'title' => 'Updated Title',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test DELETE /api/v1/packages/{documentIdentifier}
    public function testDeletePackage(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);

        // Verify package is deleted
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testDeletePackageNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendDelete('/api/v1/packages/non-existent-doc');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testDeletePackageUnauthorized(FunctionalTester $I): void
    {
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test package structure validation
    public function testPackageStructureValidation(FunctionalTester $I): void
    {
        $invalidPackageData = [
            'CFDocument' => [
                'identifier' => 'invalid-uuid',
                'title' => 'Invalid Package',
            ],
        ];

        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/api/v1/packages', $invalidPackageData);
        $I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);
    }

    // Test package with complex relationships
    public function testPackageWithComplexRelationships(FunctionalTester $I): void
    {
        $itemId1 = Uuid::uuid4()->toString();
        $itemId2 = Uuid::uuid4()->toString();

        $packageData = [
            'CFDocument' => [
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/document',
                'title' => 'Complex Package',
                'creator' => 'Test Creator',
            ],
            'CFItems' => [
                [
                    'identifier' => $itemId1,
                    'uri' => 'http://example.com/item1',
                    'fullStatement' => 'Parent Item',
                    'humanCodingScheme' => 'PAR001',
                ],
                [
                    'identifier' => $itemId2,
                    'uri' => 'http://example.com/item2',
                    'fullStatement' => 'Child Item',
                    'humanCodingScheme' => 'CHI001',
                ],
            ],
            'CFAssociations' => [
                [
                    'identifier' => Uuid::uuid4()->toString(),
                    'uri' => 'http://example.com/association',
                    'originNodeURI' => [
                        'identifier' => $itemId2,
                        'uri' => 'http://example.com/origin',
                        'title' => 'Child Item',
                    ],
                    'associationType' => 'isChildOf',
                    'destinationNodeURI' => [
                        'identifier' => $itemId1,
                        'uri' => 'http://example.com/destination',
                        'title' => 'Parent Item',
                    ],
                ],
            ],
            'CFDefinitions' => [
                'CFConcepts' => [],
                'CFSubjects' => [],
                'CFLicenses' => [],
                'CFItemTypes' => [],
                'CFAssociationGroupings' => [],
            ],
            'CFRubrics' => [],
        ];

        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/api/v1/packages', $packageData);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        // Verify relationships are maintained
        $I->seeResponseJsonMatchesJsonPath('$.CFItems[0]');
        $I->seeResponseJsonMatchesJsonPath('$.CFItems[1]');
        $I->seeResponseJsonMatchesJsonPath('$.CFAssociations[0]');
    }
}
