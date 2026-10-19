<?php

namespace Tests\Functional;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use Tests\Support\FunctionalTester;

class UriMarkdownControllerCest
{
    private const DOC_IDENTIFIER = 'a1b2c3d4-0000-4000-8000-9a2b3c4d5e6f';
    private const PARENT_IDENTIFIER = 'b2c3d4e5-0000-4000-8000-ab3c4d5e6f7a';
    private const CHILD_IDENTIFIER = 'c3d4e5f6-0000-4000-8000-bc4d5e6f7a8b';
    private const CREDENTIAL_IDENTIFIER = 'd4e5f6a7-0000-4000-8000-cd4d5e6f7a8b';

    public function _before(FunctionalTester $I): void
    {
        // The REST client sends no Accept header by default, which the uri
        // controller answers with 300 Multiple Choices; ask for markdown.
        $I->haveHttpHeader('Accept', 'text/markdown');

        $timestamp = new \DateTimeImmutable();

        $I->haveInRepository(LsDoc::class, [
            'identifier' => self::DOC_IDENTIFIER,
            'title' => 'Markdown Export Test',
            'creator' => 'Test Creator',
            'description' => 'Framework served as markdown',
            'adoptionStatus' => 'Draft',
            'statusStart' => $timestamp,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsDoc $lsDoc */
        $lsDoc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => self::DOC_IDENTIFIER]);

        $I->haveInRepository(LsItem::class, [
            'identifier' => self::PARENT_IDENTIFIER,
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'fullStatement' => '<p>Parent statement</p>',
            'abbreviatedStatement' => 'Parent item',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsItem $parent */
        $parent = $I->grabEntityFromRepository(LsItem::class, ['identifier' => self::PARENT_IDENTIFIER]);

        $I->haveInRepository(LsItem::class, [
            'identifier' => self::CHILD_IDENTIFIER,
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'fullStatement' => '<p>Child statement</p>',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsItem $child */
        $child = $I->grabEntityFromRepository(LsItem::class, ['identifier' => self::CHILD_IDENTIFIER]);

        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'originLsItem' => $child,
            'originNodeIdentifier' => $child->getIdentifier(),
            'destinationLsItem' => $parent,
            'destinationNodeIdentifier' => $parent->getIdentifier(),
            'type' => 'isChildOf',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        $I->haveInRepository(LsItem::class, [
            'identifier' => self::CREDENTIAL_IDENTIFIER,
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'discriminator' => LsItemKind::Credential->value,
            'fullStatement' => '<p>CNA description</p>',
            'abbreviatedStatement' => 'CNA',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsItem $credential */
        $credential = $I->grabEntityFromRepository(LsItem::class, ['identifier' => self::CREDENTIAL_IDENTIFIER]);
        $credential->setExtensionProperty('ob3', json_encode([
            'type' => ['Achievement'],
            'achievementType' => 'Certification',
            'name' => 'CNA',
            'description' => '<p>CNA description</p>',
            'criteria' => ['narrative' => '<p>Pass the exam</p>'],
        ], JSON_THROW_ON_ERROR));
        $em = $I->grabService('Doctrine\ORM\EntityManagerInterface');
        $em->persist($credential);
        $em->flush();
    }

    public function itemMarkdownViaExtension(FunctionalTester $I): void
    {
        $I->sendGet('/uri/'.self::PARENT_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $body = (string) $I->grabResponse();

        \PHPUnit\Framework\Assert::assertStringStartsWith("---\n", $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('objectType: Item', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('Parent item', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## Statement', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('Parent statement', $body);
    }

    public function itemMarkdownContentType(FunctionalTester $I): void
    {
        $I->sendGet('/uri/'.self::PARENT_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeaderOnce('Content-Type', 'text/markdown; charset=utf-8');
    }

    public function itemMarkdownIncludesAssociationLists(FunctionalTester $I): void
    {
        $I->sendGet('/uri/'.self::CHILD_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $childBody = (string) $I->grabResponse();
        \PHPUnit\Framework\Assert::assertStringContainsString('## Associations', $childBody);
        \PHPUnit\Framework\Assert::assertStringContainsString('### isChildOf', $childBody);
        \PHPUnit\Framework\Assert::assertStringContainsString('#### [Markdown Export Test](http://localhost/uri/'.self::DOC_IDENTIFIER.')', $childBody);
        \PHPUnit\Framework\Assert::assertStringContainsString('- [Parent item](http://localhost/uri/'.self::PARENT_IDENTIFIER.')', $childBody);

        $I->sendGet('/uri/'.self::PARENT_IDENTIFIER.'.md');
        $parentBody = (string) $I->grabResponse();
        \PHPUnit\Framework\Assert::assertStringContainsString('### isParentOf', $parentBody);
    }

    public function itemMarkdownIncludesAncestorPath(FunctionalTester $I): void
    {
        $I->sendGet('/uri/'.self::CHILD_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $body = (string) $I->grabResponse();

        \PHPUnit\Framework\Assert::assertStringContainsString('## Ancestor Path', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('1. [Markdown Export Test](http://localhost/uri/'.self::DOC_IDENTIFIER.')', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('2. [Parent item](http://localhost/uri/'.self::PARENT_IDENTIFIER.')', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString("\n3. Child statement", $body);
    }

    public function credentialMarkdownRendersKindSections(FunctionalTester $I): void
    {
        $I->sendGet('/uri/'.self::CREDENTIAL_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $body = (string) $I->grabResponse();

        \PHPUnit\Framework\Assert::assertStringContainsString('kind: credential', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## Criteria', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('Pass the exam', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('### Raw credential data', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('"achievementType"', $body);
    }

    public function markdownViaAcceptHeader(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'text/markdown');
        $I->sendGet('/uri/'.self::PARENT_IDENTIFIER);
        $I->seeResponseCodeIs(200);
        $body = (string) $I->grabResponse();
        \PHPUnit\Framework\Assert::assertStringContainsString('objectType: Item', $body);
    }

    public function tcnHeadersAdvertiseMarkdown(FunctionalTester $I): void
    {
        $I->sendGet('/uri/'.self::PARENT_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $alternates = (string) $I->grabHttpHeader('Alternates');
        \PHPUnit\Framework\Assert::assertStringContainsString('.md', $alternates);
        \PHPUnit\Framework\Assert::assertStringContainsString('text/markdown', $alternates);
    }

    public function markdownNotFound(FunctionalTester $I): void
    {
        $I->sendGet('/uri/00000000-0000-4000-8000-deadbeef0000.md');
        $I->seeResponseCodeIs(404);
        $body = (string) $I->grabResponse();
        \PHPUnit\Framework\Assert::assertStringContainsString('# Not Found', $body);
    }

    public function documentMarkdownLinksToPackage(FunctionalTester $I): void
    {
        $I->sendGet('/uri/'.self::DOC_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $body = (string) $I->grabResponse();

        \PHPUnit\Framework\Assert::assertStringContainsString('objectType: Document', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## About this framework', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## Full package', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('/uri/p'.self::DOC_IDENTIFIER, $body);
    }
}
