<?php

namespace Tests\Functional;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\ApiToken;
use App\Entity\User\User;
use Codeception\Util\HttpCode;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class ApiV1ItemControllerCest
{
    private string $apiToken;
    private User $testUser;
    private LsDoc $testDoc;
    private LsItem $testItem;

    public function _before(FunctionalTester $I): void
    {
        // Create a test user with appropriate permissions
        $I->ensureUserExistsWithRole('SUPER_EDITOR');
        $this->testUser = $I->getLastUser();

        // Create API token for the user
        $this->apiToken = $this->createApiToken($I, $this->testUser);

        // Create test document and item
        $this->testDoc = $this->createTestDocument($I);
        $this->testItem = $this->createTestItem($I, $this->testDoc);
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

    private function createTestItem(FunctionalTester $I, LsDoc $doc): LsItem
    {
        $identifier = Uuid::uuid4()->toString();
        $item = new LsItem($identifier);
        $item->setLsDoc($doc);
        $I->haveInRepository($item, [
            'fullStatement' => 'Test Item Statement',
            'humanCodingScheme' => '1.1.1',
            'listEnumInSource' => '1.1.1',
            'changedAt' => new \DateTimeImmutable(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);

        return $I->grabEntityFromRepository(LsItem::class, ['identifier' => $identifier]);
    }

    // Test POST /api/v1/packages/{documentIdentifier}/items
    public function testPostItem(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items', [
            'identifier' => Uuid::uuid4()->toString(),
            'fullStatement' => 'New Test Item Statement',
            'humanCodingScheme' => '2.1.1',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['data' => ['fullStatement' => 'New Test Item Statement']]);
    }

    public function testPostItemValidationError(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPost('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items', [
            // Missing required fields
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testPostItemUnauthorized(FunctionalTester $I): void
    {
        $I->sendPost('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items', [
            'identifier' => Uuid::uuid4()->toString(),
            'fullStatement' => 'New Test Item',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test GET /api/v1/packages/{documentIdentifier}/items/{itemIdentifier}
    public function testGetItem(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['fullStatement' => 'Test Item Statement']);
    }

    public function testGetItemNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendGet('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/non-existent-item');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    // Test PUT /api/v1/packages/{documentIdentifier}/items/{itemIdentifier}
    public function testPutItem(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier(), [
            'fullStatement' => 'Updated Test Item Statement',
            'humanCodingScheme' => '1.1.2',
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['fullStatement' => 'Updated Test Item Statement']);
    }

    public function testPutItemNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/non-existent-item', [
            'fullStatement' => 'Updated Statement',
        ]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testPutItemUnauthorized(FunctionalTester $I): void
    {
        $I->sendPut('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier(), [
            'fullStatement' => 'Updated Statement',
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test PATCH /api/v1/packages/{documentIdentifier}/items/{itemIdentifier}
    public function testPatchItem(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json-patch+json');
        $I->sendPatch('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier(), [
            [
                'op' => 'replace',
                'path' => '/fullStatement',
                'value' => 'Patched Test Item Statement',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    public function testPatchItemInvalidOperation(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json-patch+json');
        $I->sendPatch('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier(), [
            [
                'op' => 'invalid',
                'path' => '/fullStatement',
                'value' => 'Invalid Operation',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testPatchItemUnauthorized(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json-patch+json');
        $I->sendPatch('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier(), [
            [
                'op' => 'replace',
                'path' => '/fullStatement',
                'value' => 'Patched Statement',
            ],
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    // Test DELETE /api/v1/packages/{documentIdentifier}/items/{itemIdentifier}
    public function testDeleteItem(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
    }

    public function testDeleteItemNotFound(FunctionalTester $I): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/non-existent-item');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testDeleteItemUnauthorized(FunctionalTester $I): void
    {
        $I->sendDelete('/api/v1/packages/'.$this->testDoc->getIdentifier().'/items/'.$this->testItem->getIdentifier());
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }
}
