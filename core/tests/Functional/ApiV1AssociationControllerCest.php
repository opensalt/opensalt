<?php

namespace Tests\Functional;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\User\ApiToken;
use App\Entity\User\User;
use Codeception\Util\HttpCode;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class ApiV1AssociationControllerCest
{
    private string $apiToken;
    private User $testUser;
    private LsDoc $testDoc;
    private LsAssociation $testAssociation;

    public function _before(FunctionalTester $I): void
    {
        // Create a test user with appropriate permissions
        $I->ensureUserExistsWithRole('SUPER_EDITOR');
        $this->testUser = $I->getLastUser();

        // Create API token for the user
        $this->apiToken = $this->createApiToken($I, $this->testUser);

        // Create test document and association
        $this->testDoc = $this->createTestDocument($I);
        $this->testAssociation = $this->createTestAssociation($I, $this->testDoc);
    }

    private function createApiToken(FunctionalTester $I, User $user): string
    {
        $tokenEntity = ApiToken::createToken($user, 'Test Token', null);
        $em = $I->grabService('doctrine.orm.entity_manager');
        $em->persist($tokenEntity);
        $em->flush();

        return $tokenEntity->token;
    }

    private function createTestDocument(FunctionalTester $I): LsDoc
    {
        $identifier = Uuid::uuid4()->toString();
        $I->haveInRepository(LsDoc::class, [
            'identifier' => $identifier,
            'uri' => 'local:'.$identifier,
            'title' => 'Test Document',
            'creator' => 'Test Creator',
            'version' => '1.0',
            'description' => 'Test document for API testing',
            'adoptionStatus' => 'Draft',
            'statusStart' => new \DateTimeImmutable(),
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        return $I->grabEntityFromRepository(LsDoc::class, ['identifier' => $identifier]);
    }

    private function createTestAssociation(FunctionalTester $I, LsDoc $doc): LsAssociation
    {
        $identifier = Uuid::uuid4()->toString();
        $association = new LsAssociation($identifier);
        $association->setLsDoc($doc);
        $I->haveInRepository($association, [
            'type' => 'isChildOf',
            'originNodeIdentifier' => Uuid::uuid4()->toString(),
            'originNodeUri' => 'http://example.com/origin',
            'destinationNodeIdentifier' => Uuid::uuid4()->toString(),
            'destinationNodeUri' => 'http://example.com/destination',
            'sequenceNumber' => 1,
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        return $I->grabEntityFromRepository(LsAssociation::class, ['identifier' => $identifier]);
    }

    // Test POST /api/v1/packages/{documentIdentifier}/associations
    public function testPostAssociation(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations', [
            'identifier' => Uuid::uuid4()->toString(),
            'associationType' => 'isRelatedTo',
            'originNodeURI' => [
                'title' => 'Origin',
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/origin',
            ],
            'destinationNodeURI' => [
                'title' => 'Destination',
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/destination',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['data' => ['associationType' => 'isRelatedTo']]);
    }

    public function testPostAssociationValidationError(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations', [
            // Missing required fields like associationType
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testPostAssociationUnauthorized(FunctionalTester $I): void
    {
        $I->sendPost('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations', [
            'identifier' => Uuid::uuid4()->toString(),
            'associationType' => 'isRelatedTo',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test GET /api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}
    public function testGetAssociation(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/'.$this->testAssociation->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['associationType' => 'isChildOf']);
    }

    public function testGetAssociationNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/non-existent-association');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    // Test PUT /api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}
    public function testPutAssociation(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/'.$this->testAssociation->getIdentifier(), [
            'identifier' => $this->testAssociation->getIdentifier(),
            'associationType' => 'isPartOf',
            'originNodeURI' => [
                'title' => 'Origin',
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/origin',
            ],
            'destinationNodeURI' => [
                'title' => 'Destination',
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/destination',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['associationType' => 'isPartOf']);
    }

    public function testPutAssociationWithMissingRequiredData(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/'.$this->testAssociation->getIdentifier(), [
            // Missing required fields like associationType
            'identifier' => $this->testAssociation->getIdentifier(),
            'originNodeURI' => [
                'title' => 'Origin',
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/origin',
            ],
            'destinationNodeURI' => [
                'title' => 'Destination',
                'identifier' => Uuid::uuid4()->toString(),
                'uri' => 'http://example.com/destination',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testPutAssociationNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/non-existent-association', [
            'associationType' => 'isPartOf',
        ]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testPutAssociationUnauthorized(FunctionalTester $I): void
    {
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/'.$this->testAssociation->getIdentifier(), [
            'associationType' => 'isPartOf',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test DELETE /api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}
    public function testDeleteAssociation(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/'.$this->testAssociation->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
    }

    public function testDeleteAssociationNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/non-existent-association');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testDeleteAssociationUnauthorized(FunctionalTester $I): void
    {
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier().'/associations/'.$this->testAssociation->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
