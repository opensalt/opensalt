<?php

namespace Tests\Functional;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class LegacyEditorRedirectCest
{
    private LsDoc $lsDoc;

    public function _before(FunctionalTester $I): void
    {
        $identifier = Uuid::uuid4()->toString();
        $timestamp = new \DateTimeImmutable();

        $I->haveInRepository(LsDoc::class, [
            'identifier' => $identifier,
            'title' => 'Legacy Editor Redirect Test',
            'creator' => 'Test',
            'adoptionStatus' => 'Draft',
            'statusStart' => $timestamp,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        $this->lsDoc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => $identifier]);
    }

    public function cfTreeDocRedirectsToVueEditor(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->sendGet('/cf-tree/doc/'.$this->lsDoc->getSlug());
        $I->seeResponseCodeIs(301);
        $I->seeHttpHeader('Location', '/editor/'.$this->lsDoc->getIdentifier());
    }

    public function cfTreeAssociationViewRedirectsToVueEditor(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->sendGet('/cf-tree/doc/'.$this->lsDoc->getSlug().'/av');
        $I->seeResponseCodeIs(301);
        $I->seeHttpHeader('Location', '/editor/'.$this->lsDoc->getIdentifier().'/association');
    }

    public function cftreeNumericDocRedirectsToVueEditor(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->sendGet('/cftree/doc/'.$this->lsDoc->getId());
        $I->seeResponseCodeIs(302);
        $I->seeHttpHeader('Location', '/editor/'.$this->lsDoc->getIdentifier());
    }

    public function cfDocViewRedirectsToVueEditor(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->sendGet('/cf/doc/'.$this->lsDoc->getId());
        $I->seeResponseCodeIs(301);
        $I->seeHttpHeader('Location', '/editor/'.$this->lsDoc->getIdentifier());
    }

    public function cfItemViewRedirectsToVueEditor(FunctionalTester $I): void
    {
        $itemIdentifier = Uuid::uuid4()->toString();
        $I->haveInRepository(LsItem::class, [
            'identifier' => $itemIdentifier,
            'lsDoc' => $this->lsDoc,
            'lsDocIdentifier' => $this->lsDoc->getIdentifier(),
            'fullStatement' => 'Legacy Redirect Test Item',
            'humanCodingScheme' => 'TEST.1',
            'listEnumInSource' => '1',
            'changedAt' => new \DateTimeImmutable(),
        ]);
        $lsItem = $I->grabEntityFromRepository(LsItem::class, ['identifier' => $itemIdentifier]);

        $I->stopFollowingRedirects();
        $I->sendGet('/cf/item/'.$lsItem->getId());
        $I->seeResponseCodeIs(301);
        $I->seeHttpHeader(
            'Location',
            '/editor/'.$this->lsDoc->getIdentifier().'/'.$lsItem->getIdentifier()
        );
    }

    public function cfDocJsonExportStillWorks(FunctionalTester $I): void
    {
        $I->sendGet('/cf/doc/'.$this->lsDoc->getId().'.json');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    public function removedRemoteFrameworkRoutesReturnNotFound(FunctionalTester $I): void
    {
        $I->stopFollowingRedirects();

        $I->sendGet('/cf-tree/remote');
        $I->seeResponseCodeIs(404);

        // GET used to load the remote framework page; only the DELETE route on
        // /cfdoc/{id} remains, so a GET is answered with 405, not 404.
        $I->sendGet('/cfdoc/remote');
        $I->seeResponseCodeIs(405);
    }
}
