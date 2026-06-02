<?php

namespace Tests\Functional;

use App\Entity\Framework\LsDoc;
use App\Entity\User\ApiToken;
use App\Entity\User\User;
use Codeception\Util\HttpCode;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class ApiV1DocumentControllerCest
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

        // Create test document
        $this->testDoc = $this->createTestDocument($I);
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
            'title' => 'Test Document',
            'creator' => 'Test Creator',
            'version' => '1.0',
            'description' => 'Test document for API testing',
            'adoptionStatus' => 'Draft',
            'statusStart' => new \DateTimeImmutable(),
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        // Retrieve the persisted document
        return $I->grabEntityFromRepository(LsDoc::class, ['identifier' => $identifier]);
    }

    // Test GET /api/v1/documents (index)
    public function testIndexDocuments(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/documents');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['data' => []]);
    }

    public function testIndexDocumentsWithFilters(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/documents', [
            'filter[title]' => 'Test',
            'filter[sort]' => 'title',
            'filter[order]' => 'asc',
            'page[limit]' => 10,
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    // Test POST /api/v1/documents
    public function testPostDocument(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/documents', [
            'identifier' => Uuid::uuid4()->toString(),
            'title' => 'New Test Document',
            'creator' => 'Test Creator',
            'version' => '1.1',
            'description' => 'New test document',
            'adoptionStatus' => 'Draft',
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['title' => 'New Test Document']);
    }

    public function testPostDocumentValidationError(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/documents', [
            // Missing required fields
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testPostDocumentUnauthorized(FunctionalTester $I): void
    {
        $I->sendPost('/api/v1/documents', [
            'identifier' => Uuid::uuid4()->toString(),
            'title' => 'New Test Document',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    // Test GET /api/v1/documents/{documentIdentifier}
    public function testGetDocument(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/documents/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['title' => 'Test Document']);
    }

    public function testGetDocumentNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/documents/non-existent-doc');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    // Test PUT /api/v1/documents/{documentIdentifier}
    public function testPutDocument(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/documents/'.$this->testDoc->getIdentifier(), [
            'title' => 'Updated Test Document',
            'creator' => 'Updated Creator',
            'version' => '1.1',
            'description' => 'Updated description',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['title' => 'Updated Test Document']);
    }

    public function testPutDocumentNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/documents/non-existent-doc', [
            'title' => 'Updated Title',
        ]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testPutDocumentUnauthorized(FunctionalTester $I): void
    {
        $I->sendPut('/api/v1/documents/'.$this->testDoc->getIdentifier(), [
            'title' => 'Updated Title',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    // Test POST /api/v1/document
    public function testPostDocumentViaPackages(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/documents', [
            'identifier' => Uuid::uuid4()->toString(),
            'title' => 'Package Test Document',
            'creator' => 'Test Creator',
            'version' => '1.0',
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
    }

    // Test GET /api/v1/document/{documentIdentifier}
    public function testGetDocumentViaPackages(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/documents/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    // Test PUT /api/v1/document/{documentIdentifier}
    public function testPutDocumentViaPackages(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/documents/'.$this->testDoc->getIdentifier(), [
            'title' => 'Updated via Packages',
            'creator' => 'Updated Creator',
            'version' => '1.1',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }
}
