<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Entity\User\ApiToken;
use App\Entity\User\User;
use Codeception\Util\HttpCode;
use Ramsey\Uuid\Uuid;
use Tests\Support\FunctionalTester;

class ArticulationEvaluateCest
{
    private string $apiToken;

    private LsDoc $articulationDoc;

    public function _before(FunctionalTester $I): void
    {
        $I->ensureUserExistsWithRole('SUPER_EDITOR');
        $testUser = $I->getLastUser();

        $this->apiToken = $this->createApiToken($I, $testUser);
        $this->articulationDoc = $this->createArticulationFixture($I);
    }

    public function testEvaluateReturnsSatisfiedForMatchedOrRequirement(FunctionalTester $I): void
    {
        $this->sendEvaluateRequest($I, [
            'sendingInstitution' => [
                'identifiers' => [
                    ['type' => 'coci:schoolId', 'value' => '46'],
                ],
            ],
            'receivingInstitution' => [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'SJSU'],
                ],
            ],
            'sendingCourses' => [
                [
                    'identifiers' => [
                        ['type' => 'courseCode', 'value' => 'MATH 10'],
                    ],
                ],
            ],
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.results[0]');
        $I->seeResponseContainsJson([
            'articulationDocument' => [
                'identifier' => $this->articulationDoc->getIdentifier(),
            ],
            'academicYear' => '2025-2026',
            'results' => [
                [
                    'status' => 'satisfied',
                    'articulationKey' => '76/51/to/39/Department/8047:2',
                    'receiving' => [
                        'course' => [
                            'courseCode' => 'JS 15',
                        ],
                    ],
                    'requirement' => [
                        'op' => 'anyOf',
                        'status' => 'satisfied',
                    ],
                ],
            ],
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.results[0].paths.options');
    }

    public function testEvaluateReturnsPartialForMatchedAndRequirement(FunctionalTester $I): void
    {
        $this->sendEvaluateRequest($I, [
            'sendingInstitution' => [
                'identifiers' => [
                    ['type' => 'coci:schoolId', 'value' => '46'],
                ],
            ],
            'receivingInstitution' => [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'SJSU'],
                ],
            ],
            'sendingCourses' => [
                [
                    'identifiers' => [
                        ['type' => 'courseCode', 'value' => 'CHEM 12A'],
                    ],
                ],
            ],
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'results' => [
                [
                    'status' => 'partial',
                    'articulationKey' => 'and-group-key',
                    'receiving' => [
                        'course' => [
                            'courseCode' => 'ENGR 100',
                        ],
                    ],
                    'requirement' => [
                        'op' => 'allOf',
                        'status' => 'partial',
                    ],
                ],
            ],
        ]);
    }

    public function testEvaluateUnauthorizedWithoutToken(FunctionalTester $I): void
    {
        $I->deleteHeader('Authorization');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendPost('/api/v1/articulations/evaluate', [
            'sendingInstitution' => [
                'identifiers' => [
                    ['type' => 'coci:schoolId', 'value' => '46'],
                ],
            ],
            'receivingInstitution' => [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'SJSU'],
                ],
            ],
            'sendingCourses' => [],
        ]);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function testEvaluateNotFoundForUnknownInstitutions(FunctionalTester $I): void
    {
        $this->sendEvaluateRequest($I, [
            'sendingInstitution' => [
                'identifiers' => [
                    ['type' => 'coci:schoolId', 'value' => 'UNKNOWN_SENDING'],
                ],
            ],
            'receivingInstitution' => [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'UNKNOWN_RECEIVING'],
                ],
            ],
            'sendingCourses' => [
                [
                    'identifiers' => [
                        ['type' => 'courseCode', 'value' => 'MATH 10'],
                    ],
                ],
            ],
        ]);

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testEvaluateReturnsEmptyResultsForEmptySendingCourses(FunctionalTester $I): void
    {
        $this->sendEvaluateRequest($I, [
            'sendingInstitution' => [
                'identifiers' => [
                    ['type' => 'coci:schoolId', 'value' => '46'],
                ],
            ],
            'receivingInstitution' => [
                'identifiers' => [
                    ['type' => 'courseCode', 'value' => 'SJSU'],
                ],
            ],
            'sendingCourses' => [],
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'results' => [],
        ]);
    }

    private function createApiToken(FunctionalTester $I, User $user): string
    {
        $tokenEntity = ApiToken::createToken($user, 'Articulation Evaluate Test Token', null);
        $em = $I->grabService('doctrine.orm.entity_manager');
        $em->persist($tokenEntity);
        $em->flush();

        return $tokenEntity->token;
    }

    private function createArticulationFixture(FunctionalTester $I): LsDoc
    {
        $timestamp = new \DateTimeImmutable();
        $docIdentifier = Uuid::uuid4()->toString();

        $I->haveInRepository(LsDoc::class, [
            'identifier' => $docIdentifier,
            'title' => 'Foothill to SJSU Articulation (Test)',
            'creator' => 'Test Creator',
            'version' => '1.0',
            'description' => 'Mini articulation graph for evaluate functional tests',
            'adoptionStatus' => 'Draft',
            'statusStart' => $timestamp,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
            'extensions' => [
                'ais:sendingInstitution' => ['code' => 'FOOTHILL', 'id' => 51],
                'ais:receivingInstitution' => ['code' => 'SJSU    ', 'id' => 39],
                'ais:academicYear' => ['code' => '2025-2026'],
            ],
        ]);

        /** @var LsDoc $doc */
        $doc = $I->grabEntityFromRepository(LsDoc::class, ['identifier' => $docIdentifier]);

        $sendingOrg = $this->createOrganizationItem(
            $I,
            $doc,
            'FOOTHILL (College)',
            'FOOTHILL',
            '422',
            [
                'salt:type' => 'organization',
                'coci:schoolId' => '46',
                'codes:etsCode' => '4315',
                'codes:misCode' => '422',
            ],
            $timestamp,
        );
        $receivingOrg = $this->createOrganizationItem(
            $I,
            $doc,
            'San Jose State University (University)',
            'SJSU',
            'SJSU',
            [
                'salt:type' => 'organization',
                'codes:etsCode' => '4687',
            ],
            $timestamp,
        );

        $this->createInstitutionAssociation($I, $doc, $sendingOrg, 'ext:sendingInstitution', $timestamp);
        $this->createInstitutionAssociation($I, $doc, $receivingOrg, 'ext:receivingInstitution', $timestamp);

        $requirementSet = $this->createItem(
            $I,
            $doc,
            'Requirement Set: MATH 10 or SOC 7',
            null,
            'Requirement Set',
            $timestamp,
        );
        $math10 = $this->createItem($I, $doc, 'MATH 10', 'MATH 10', 'Course', $timestamp);
        $soc7 = $this->createItem($I, $doc, 'SOC 7', 'SOC 7', 'Course', $timestamp);
        $js15 = $this->createItem($I, $doc, 'JS 15', 'JS 15', 'Course', $timestamp);

        $requirementGroup = $this->createItem(
            $I,
            $doc,
            'Requirement Group: CHEM 12A and CHEM 12B',
            null,
            'Requirement Group',
            $timestamp,
        );
        $chem12a = $this->createItem($I, $doc, 'CHEM 12A', 'CHEM 12A', 'Course', $timestamp);
        $chem12b = $this->createItem($I, $doc, 'CHEM 12B', 'CHEM 12B', 'Course', $timestamp);
        $engr100 = $this->createItem($I, $doc, 'ENGR 100', 'ENGR 100', 'Course', $timestamp);

        $this->createPartOfAssociation($I, $doc, $math10, $requirementSet, $timestamp);
        $this->createPartOfAssociation($I, $doc, $soc7, $requirementSet, $timestamp);
        $this->createArticulationAssociation(
            $I,
            $doc,
            $requirementSet,
            $js15,
            '76/51/to/39/Department/8047:2',
            $timestamp,
        );

        $this->createPartOfAssociation($I, $doc, $chem12a, $requirementGroup, $timestamp);
        $this->createPartOfAssociation($I, $doc, $chem12b, $requirementGroup, $timestamp);
        $this->createArticulationAssociation(
            $I,
            $doc,
            $requirementGroup,
            $engr100,
            'and-group-key',
            $timestamp,
        );

        return $doc;
    }

    private function createItem(
        FunctionalTester $I,
        LsDoc $doc,
        string $fullStatement,
        ?string $courseCode,
        string $itemType,
        \DateTimeImmutable $timestamp,
    ): LsItem {
        $identifier = Uuid::uuid4()->toString();
        $I->haveInRepository(LsItem::class, [
            'identifier' => $identifier,
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'fullStatement' => $fullStatement,
            'humanCodingScheme' => $courseCode,
            'itemTypeText' => $itemType,
            'discriminator' => 'Course' === $itemType ? LsItemKind::Course->value : LsItemKind::Default->value,
            'listEnumInSource' => '1',
            'abbreviatedStatement' => $fullStatement,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        /** @var LsItem $item */
        $item = $I->grabEntityFromRepository(LsItem::class, ['identifier' => $identifier]);

        return $item;
    }

    /**
     * @param array<string, mixed> $extensions
     */
    private function createOrganizationItem(
        FunctionalTester $I,
        LsDoc $doc,
        string $fullStatement,
        string $abbreviatedStatement,
        string $humanCodingScheme,
        array $extensions,
        \DateTimeImmutable $timestamp,
    ): LsItem {
        $identifier = Uuid::uuid4()->toString();
        $I->haveInRepository(LsItem::class, [
            'identifier' => $identifier,
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'fullStatement' => $fullStatement,
            'abbreviatedStatement' => $abbreviatedStatement,
            'humanCodingScheme' => $humanCodingScheme,
            'itemTypeText' => 'Organization',
            'discriminator' => LsItemKind::Organization->value,
            'listEnumInSource' => '1',
            'extensions' => $extensions,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);

        /** @var LsItem $item */
        $item = $I->grabEntityFromRepository(LsItem::class, ['identifier' => $identifier]);

        return $item;
    }

    private function createInstitutionAssociation(
        FunctionalTester $I,
        LsDoc $doc,
        LsItem $organization,
        string $type,
        \DateTimeImmutable $timestamp,
    ): void {
        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'originLsDoc' => $doc,
            'originNodeIdentifier' => $doc->getIdentifier(),
            'destinationLsItem' => $organization,
            'destinationLsDoc' => $doc,
            'destinationNodeIdentifier' => $organization->getIdentifier(),
            'type' => $type,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
    }

    private function createPartOfAssociation(
        FunctionalTester $I,
        LsDoc $doc,
        LsItem $origin,
        LsItem $destination,
        \DateTimeImmutable $timestamp,
    ): void {
        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'originLsItem' => $origin,
            'originNodeIdentifier' => $origin->getIdentifier(),
            'destinationLsItem' => $destination,
            'destinationLsDoc' => $doc,
            'destinationNodeIdentifier' => $destination->getIdentifier(),
            'type' => LsAssociation::PART_OF,
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
    }

    private function createArticulationAssociation(
        FunctionalTester $I,
        LsDoc $doc,
        LsItem $origin,
        LsItem $destination,
        string $articulationKey,
        \DateTimeImmutable $timestamp,
    ): void {
        $I->haveInRepository(LsAssociation::class, [
            'lsDoc' => $doc,
            'lsDocIdentifier' => $doc->getIdentifier(),
            'originLsItem' => $origin,
            'originNodeIdentifier' => $origin->getIdentifier(),
            'destinationLsItem' => $destination,
            'destinationLsDoc' => $doc,
            'destinationNodeIdentifier' => $destination->getIdentifier(),
            'type' => 'ext:articulation',
            'extensions' => [
                'ais:articulationKey' => $articulationKey,
            ],
            'changedAt' => $timestamp,
            'updatedAt' => $timestamp,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendEvaluateRequest(FunctionalTester $I, array $payload): void
    {
        $I->amBearerAuthenticated($this->apiToken);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendPost('/api/v1/articulations/evaluate', $payload);
    }
}
