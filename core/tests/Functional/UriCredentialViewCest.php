<?php

namespace Tests\Functional;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use Tests\Support\FunctionalTester;

class UriCredentialViewCest
{
    private const DOC_IDENTIFIER = 'b1c2d3e4-0000-4000-8000-1b2b3c4d5e6f';
    private const SPARSE_CREDENTIAL_IDENTIFIER = 'c2d3e4f5-0000-4000-8000-2c3c4d5e6f7a';

    public function _before(FunctionalTester $I): void
    {
        $timestamp = new \DateTimeImmutable();

        $I->haveInRepository(LsDoc::class, [
            'identifier' => self::DOC_IDENTIFIER,
            'title' => 'Sparse Credential Test',
            'creator' => 'Test Creator',
            'adoptionStatus' => 'Draft',
            'statusStart' => $timestamp,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsDoc $lsDoc */
        $lsDoc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => self::DOC_IDENTIFIER]);

        $I->haveInRepository(LsItem::class, [
            'identifier' => self::SPARSE_CREDENTIAL_IDENTIFIER,
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'discriminator' => LsItemKind::Credential->value,
            'fullStatement' => 'A credential with a minimal ob3 payload',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsItem $credential */
        $credential = $I->grabEntityFromRepository(LsItem::class, ['identifier' => self::SPARSE_CREDENTIAL_IDENTIFIER]);
        $credential->setExtensionProperty('ob3', json_encode([
            'type' => ['Achievement'],
        ], JSON_THROW_ON_ERROR));
        $em = $I->grabService('Doctrine\ORM\EntityManagerInterface');
        $em->persist($credential);
        $em->flush();
    }

    public function htmlViewRendersSparseCredential(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'text/html');
        $I->sendGet('/uri/'.self::SPARSE_CREDENTIAL_IDENTIFIER.'.html');
        $I->seeResponseCodeIs(200);
        $body = (string) $I->grabResponse();
        \PHPUnit\Framework\Assert::assertStringNotContainsString('Warning', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('Achievement Type:', $body);
    }

    public function jsonLdViewRendersSparseCredential(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/ld+json');
        $I->sendGet('/uri/'.self::SPARSE_CREDENTIAL_IDENTIFIER.'.jsonld');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'achievementType' => 'Achievement',
        ]);
    }
}
