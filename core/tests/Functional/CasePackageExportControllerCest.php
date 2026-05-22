<?php

namespace Tests\Functional;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Codeception\Util\HttpCode;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class CasePackageExportControllerCest
{
    private LsDoc $testDoc;
    private LsDefAssociationGrouping $unusedGrouping;

    public function _before(FunctionalTester $I): void
    {
        $this->testDoc = $this->createTestPackage($I);
        $this->unusedGrouping = $this->createUnusedAssociationGrouping($I, $this->testDoc);
    }

    private function createTestPackage(FunctionalTester $I): LsDoc
    {
        $docIdentifier = Uuid::uuid4()->toString();
        $timestamp = new \DateTimeImmutable();

        $I->haveInRepository(LsDoc::class, [
            'identifier' => $docIdentifier,
            'title' => 'CASE Package Export Test',
            'creator' => 'Test Creator',
            'version' => '1.0',
            'description' => 'Test package for CASE export regression',
            'adoptionStatus' => 'Draft',
            'statusStart' => $timestamp,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        /** @var LsDoc $doc */
        $doc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => $docIdentifier]);

        $itemIdentifier = Uuid::uuid4()->toString();
        $I->haveInRepository(LsItem::class, [
            'identifier' => $itemIdentifier,
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'fullStatement' => 'Test Item Statement',
            'humanCodingScheme' => 'TEST.1',
            'listEnumInSource' => '1',
            'abbreviatedStatement' => 'Test Item',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        /** @var LsItem $item */
        $item = $I->grabEntityFromRepository(LsItem::class, ['identifier' => $itemIdentifier]);
        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'originLsItem' => $item,
            'originNodeIdentifier' => $item->getIdentifier(),
            'destinationLsDoc' => $doc,
            'destinationNodeIdentifier' => $doc->getIdentifier(),
            'type' => 'isChildOf',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        return $doc;
    }

    private function createUnusedAssociationGrouping(FunctionalTester $I, LsDoc $doc): LsDefAssociationGrouping
    {
        $identifier = Uuid::uuid4()->toString();
        $timestamp = new \DateTimeImmutable();

        $I->haveInRepository(LsDefAssociationGrouping::class, [
            'identifier' => $identifier,
            'lsDoc' => $doc,
            'title' => 'Unused Group',
            'description' => 'Document-owned grouping with no associations',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        /** @var LsDefAssociationGrouping $grouping */
        $grouping = $I->grabEntityFromRepository(LsDefAssociationGrouping::class, ['identifier' => $identifier]);

        return $grouping;
    }

    public function testV1p1PackageIncludesDocumentOwnedUnusedAssociationGrouping(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/ims/case/v1p1/CFPackages/'.$this->testDoc->getIdentifier().'.json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.CFDefinitions.CFAssociationGroupings[0]');
        $I->seeResponseContainsJson([
            'CFDefinitions' => [
                'CFAssociationGroupings' => [
                    [
                        'identifier' => $this->unusedGrouping->getIdentifier(),
                        'title' => $this->unusedGrouping->getTitle(),
                    ],
                ],
            ],
        ]);
    }

    public function testV1p0PackageKeepsUnusedAssociationGroupingOutOfExport(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGet('/ims/case/v1p0/CFPackages/'.$this->testDoc->getIdentifier().'.json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson([
            'CFDefinitions' => [
                'CFAssociationGroupings' => [
                    [
                        'identifier' => $this->unusedGrouping->getIdentifier(),
                    ],
                ],
            ],
        ]);
    }
}
