<?php

namespace Tests\Functional;

use App\Entity\Framework\LsDoc;
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

        return $I->grabEntityFromRepository(LsDoc::class, ['identifier' => $identifier]);
    }

    // Test GET /api/v1/packages/{documentIdentifier}
    public function testGetPackage(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['title' => 'Test Document']);
    }

    public function testGetPackageNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages/non-existent-doc');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    /*
    public function testGetPackageUnauthorized(FunctionalTester $I): void
    {
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
    */

    // Test POST /api/v1/packages
    public function testPostPackage(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/packages', [
            'identifier' => Uuid::uuid4()->toString(),
            'title' => 'New Test Package',
            'creator' => 'Test Creator',
            'version' => '1.0',
            'description' => 'New test package',
            'adoptionStatus' => 'Draft',
        ]);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['title' => 'New Test Package']);
    }

    public function testPostPackageValidationError(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/packages', [
            // Missing required fields
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testPostPackageUnauthorized(FunctionalTester $I): void
    {
        $I->sendPost('/api/v1/packages', [
            'identifier' => Uuid::uuid4()->toString(),
            'title' => 'New Test Package',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test PUT /api/v1/packages/{documentIdentifier}
    public function testPutPackage(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier(), [
            'title' => 'Updated Test Package',
            'creator' => 'Updated Creator',
            'version' => '1.1',
            'description' => 'Updated package description',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['title' => 'Updated Test Package']);
    }

    public function testPutPackageNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/packages/non-existent-doc', [
            'title' => 'Updated Title',
        ]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testPutPackageUnauthorized(FunctionalTester $I): void
    {
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier(), [
            'title' => 'Updated Title',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test DELETE /api/v1/packages/{documentIdentifier}
    public function testDeletePackage(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
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
}
