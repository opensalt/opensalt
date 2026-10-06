<?php

namespace Tests\Functional;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class UriPackageControllerCest
{
    private const DOC_IDENTIFIER = 'a1b2c3d4-0000-4000-8000-1a2b3c4d5e6f';
    private const ITEM_ONE_IDENTIFIER = 'b2c3d4e5-0000-4000-8000-2b3c4d5e6f7a';
    private const ITEM_TWO_IDENTIFIER = 'c3d4e5f6-0000-4000-8000-3c4d5e6f7a8b';

    private LsDoc $lsDoc;

    public function _before(FunctionalTester $I): void
    {
        $timestamp = new \DateTimeImmutable();

        $I->haveInRepository(LsDoc::class, [
            'identifier' => self::DOC_IDENTIFIER,
            'title' => 'URI Package Export Test',
            'creator' => 'Test Creator',
            'description' => 'Package served through the /uri/ endpoint',
            'adoptionStatus' => 'Draft',
            'statusStart' => $timestamp,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        $this->lsDoc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => self::DOC_IDENTIFIER]);

        foreach ([self::ITEM_ONE_IDENTIFIER => 'Export item one', self::ITEM_TWO_IDENTIFIER => 'Export item two'] as $identifier => $statement) {
            $I->haveInRepository(LsItem::class, [
                'identifier' => $identifier,
                'lsDoc' => $this->lsDoc,
                'lsDocIdentifier' => $this->lsDoc->getIdentifier(),
                'fullStatement' => $statement,
                'humanCodingScheme' => 'TEST.'.$identifier[0],
                'listEnumInSource' => '1',
                'changedAt' => $timestamp,
                'updatedAt' => $timestamp,
            ]);
        }

        /** @var LsItem $itemOne */
        $itemOne = $I->grabEntityFromRepository(LsItem::class, ['identifier' => self::ITEM_ONE_IDENTIFIER]);
        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $this->lsDoc,
            'lsDocIdentifier' => $this->lsDoc->getIdentifier(),
            'originLsItem' => $itemOne,
            'originNodeIdentifier' => $itemOne->getIdentifier(),
            'destinationLsDoc' => $this->lsDoc,
            'destinationNodeIdentifier' => $this->lsDoc->getIdentifier(),
            'type' => 'isChildOf',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
    }

    public function packageJsonExportContainsDocumentAndItems(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/uri/p'.self::DOC_IDENTIFIER.'.json');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'CFDocument' => [
                'identifier' => self::DOC_IDENTIFIER,
                'title' => 'URI Package Export Test',
            ],
        ]);
        $I->seeResponseContains('"fullStatement":"Export item one"');
        $I->seeResponseContains('"fullStatement":"Export item two"');
        $I->seeResponseContains('"isChildOf"');
    }

    public function packageJsonLdExportAddsContext(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/ld+json');
        $I->sendGet('/uri/p'.self::DOC_IDENTIFIER.'.jsonld');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            '@context' => 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld',
            'type' => 'CFPackage',
        ]);
    }

    public function packageNdjsonExportStreamsDocumentFirstThenItemsThenAssociations(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/x-ndjson');
        $I->sendGet('/uri/p'.self::DOC_IDENTIFIER.'.ndjson');
        $I->seeResponseCodeIs(200);

        $lines = array_values(array_filter(explode("\n", trim($I->grabResponse()))));
        $I->assertGreaterThan(3, count($lines), 'Expected document, item and association lines');

        $documentLine = $lines[0];
        $I->assertStringContainsString(self::DOC_IDENTIFIER, $documentLine, 'The document must be streamed first');
        $I->assertStringContainsString('URI Package Export Test', $documentLine);

        $itemLineIndexes = [];
        $associationLineIndex = null;
        foreach ($lines as $index => $line) {
            if (str_contains($line, 'Export item one') || str_contains($line, 'Export item two')) {
                $itemLineIndexes[] = $index;
            }
            if (str_contains($line, 'isChildOf')) {
                $associationLineIndex = $index;
            }
        }

        $I->assertCount(2, $itemLineIndexes, 'Both items must be streamed as their own lines');
        foreach ($itemLineIndexes as $index) {
            $I->assertGreaterThan(0, $index, 'Items must come after the document line');
        }
        $I->assertNotNull($associationLineIndex, 'The association must be streamed');
        $I->assertGreaterThan(max($itemLineIndexes), $associationLineIndex, 'Associations must come after items');
    }

    public function packageCsvExportReturnsCsv(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'text/csv');
        $I->sendGet('/uri/p'.self::DOC_IDENTIFIER.'.csv');
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'text/csv; charset=UTF-8');
        $I->assertStringContainsString('URI Package Export Test', $I->grabResponse());
    }

    public function singleItemUriReturnsItemJson(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/uri/'.self::ITEM_ONE_IDENTIFIER.'.json');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'identifier' => self::ITEM_ONE_IDENTIFIER,
            'fullStatement' => 'Export item one',
        ]);
    }

    public function unknownPackageUriReturnsNotFound(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/uri/p'.Uuid::uuid4()->toString().'.json');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContains('was not found');
    }
}
