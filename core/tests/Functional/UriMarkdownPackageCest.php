<?php

namespace Tests\Functional;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Tests\Support\FunctionalTester;

class UriMarkdownPackageCest
{
    private const DOC_IDENTIFIER = 'e5f6a7b8-0000-4000-8000-da2b3c4d5e6f';
    private const PARENT_IDENTIFIER = 'f6a7b8c9-0000-4000-8000-eb3c4d5e6f7a';
    private const CHILD_IDENTIFIER = 'a7b8c9d0-0000-4000-8000-fc4d5e6f7a8b';

    public function _before(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Accept', 'text/markdown');

        $timestamp = new \DateTimeImmutable();

        $I->haveInRepository(LsDoc::class, [
            'identifier' => self::DOC_IDENTIFIER,
            'title' => 'Markdown Package Test',
            'creator' => 'Test Creator',
            'description' => 'Package as markdown',
            'adoptionStatus' => 'Draft',
            'statusStart' => $timestamp,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsDoc $lsDoc */
        $lsDoc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => self::DOC_IDENTIFIER]);

        $I->haveInRepository(\App\Entity\Framework\LsDefSubject::class, [
            'title' => 'Testing',
            'description' => 'Test subject',
            'hierarchyCode' => '1',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var \App\Entity\Framework\LsDefSubject $subject */
        $subject = $I->grabEntityFromRepository(\App\Entity\Framework\LsDefSubject::class, ['title' => 'Testing']);
        $lsDoc->addSubject($subject);
        $em = $I->grabService('Doctrine\ORM\EntityManagerInterface');
        $em->persist($lsDoc);
        $em->flush();

        $I->haveInRepository(LsItem::class, [
            'identifier' => self::PARENT_IDENTIFIER,
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'fullStatement' => 'Package parent',
            'abbreviatedStatement' => 'Package parent',
            'listEnumInSource' => '1',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
        /** @var LsItem $parent */
        $parent = $I->grabEntityFromRepository(LsItem::class, ['identifier' => self::PARENT_IDENTIFIER]);

        $I->haveInRepository(LsItem::class, [
            'identifier' => self::CHILD_IDENTIFIER,
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'fullStatement' => 'Package child',
            'abbreviatedStatement' => 'Package child',
            'humanCodingScheme' => 'PC-1',
            'listEnumInSource' => '1',
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
        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $lsDoc,
            'lsDocIdentifier' => $lsDoc->getIdentifier(),
            'originLsItem' => $parent,
            'originNodeIdentifier' => $parent->getIdentifier(),
            'destinationLsDoc' => $lsDoc,
            'destinationNodeIdentifier' => $lsDoc->getIdentifier(),
            'type' => 'isChildOf',
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
    }

    public function packageMarkdownIsSelfContained(FunctionalTester $I): void
    {
        $I->sendGet('/uri/p'.self::DOC_IDENTIFIER.'.md');
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeaderOnce('Content-Type', 'text/markdown; charset=utf-8');

        $body = (string) $I->grabResponse();

        \PHPUnit\Framework\Assert::assertStringContainsString('objectType: Package', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('itemCount: 2', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## About this framework', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## Contents', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('- [1 Package parent](http://localhost/uri/'.self::PARENT_IDENTIFIER.')', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('- [1.1 Package child (PC-1)](http://localhost/uri/'.self::CHILD_IDENTIFIER.')', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('### 1 ', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('### 1.1 ', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('Package parent', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('Package child', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## Document-level associations', $body);
        \PHPUnit\Framework\Assert::assertStringContainsString('## Definitions', $body);
    }
}
