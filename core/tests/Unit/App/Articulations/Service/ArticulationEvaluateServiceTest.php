<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\Identifier;
use App\Articulations\Model\InstitutionMatch;
use App\Articulations\Service\ArticulationEvaluateService;
use App\Articulations\Service\CourseCodeNormalizer;
use App\Articulations\Service\EntityIdentifierIndex;
use App\Articulations\Service\IdentifierMatcher;
use App\Articulations\Service\RequirementEvaluator;
use App\Articulations\Service\RequirementTreeBuilder;
use App\Articulations\Service\SendingCourseMatcher;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Entity\Framework\IdentifiableInterface;
use App\Repository\Framework\LsAssociationRepository;
use App\Repository\Framework\LsItemRepository;
use App\Service\Api1Uris;
use PHPUnit\Framework\TestCase;

final class ArticulationEvaluateServiceTest extends TestCase
{
    public function testEvaluateDocumentReturnsSatisfiedResultForMatchedAnyOfArticulation(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');

        $set = $this->mockItem('set-1', 'local:set-1', null, 'Requirement Set');
        $math10 = $this->mockItem('course-a', 'local:course-a', 'MATH 10', 'Course');
        $soc7 = $this->mockItem('course-b', 'local:course-b', 'SOC 7', 'Course');
        $js15 = $this->mockItem('course-recv', 'local:course-recv', 'JS 15', 'Course');

        $items = [$set, $math10, $soc7, $js15];
        $associations = [
            $this->mockPartOf('course-a', 'set-1'),
            $this->mockPartOf('course-b', 'set-1'),
            $this->mockArticulation('set-1', 'course-recv', '76/51/to/39/Department/8047:2'),
        ];

        $service = $this->createService($items, $associations);

        $sendingMatch = $this->institutionMatch('send-org', 'coci:schoolId', '46', legalName: 'Foothill College');
        $receivingMatch = $this->institutionMatch('recv-org', 'courseCode', 'SJSU', abbreviatedStatement: 'San José State University');
        $sendingCourses = [
            EntityIdentifiers::fromArray([
                ['type' => 'courseCode', 'value' => 'MATH 10'],
            ]),
        ];

        $payload = $service->evaluateDocument($doc, $sendingMatch, $receivingMatch, $sendingCourses);

        $this->assertSame('Foothill College', $payload['sendingInstitution']['name']);
        $this->assertSame([
            ['type' => 'coci:schoolId', 'value' => '46'],
            ['type' => 'identifier', 'value' => 'send-org'],
        ], $payload['sendingInstitution']['identifiers']);
        $this->assertSame('San José State University', $payload['receivingInstitution']['name']);
        $this->assertSame([
            ['type' => 'courseCode', 'value' => 'SJSU'],
            ['type' => 'identifier', 'value' => 'recv-org'],
        ], $payload['receivingInstitution']['identifiers']);
        $this->assertSame([
            'identifier' => 'doc-1',
            'uri' => 'https://example.test/uri/doc-1',
        ], $payload['articulationDocument']);
        $this->assertSame('2025-2026', $payload['academicYear']);
        $this->assertCount(1, $payload['results']);

        $result = $payload['results'][0];
        $this->assertSame('satisfied', $result['status']);
        $this->assertSame('76/51/to/39/Department/8047:2', $result['articulationKey']);
        $this->assertSame('JS 15', $result['receiving']['course']['courseCode']);
        $this->assertSame('https://example.test/uri/course-recv', $result['receiving']['course']['uri']);
        $this->assertSame('anyOf', $result['requirement']['op']);
        $this->assertSame('satisfied', $result['requirement']['status']);
        $this->assertTrue($result['requirement']['members'][0]['match']);
        $this->assertSame('https://example.test/uri/course-a', $result['requirement']['members'][0]['uri']);
        $this->assertFalse($result['requirement']['members'][1]['match']);
        $this->assertArrayHasKey('paths', $result);
        $this->assertFalse($result['paths']['truncated']);
        $this->assertNotEmpty($result['paths']['options']);
    }

    public function testEvaluateDocumentReturnsEmptyResultsWhenNoSendingCourses(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');

        $set = $this->mockItem('set-1', 'local:set-1', null, 'Requirement Set');
        $math10 = $this->mockItem('course-a', 'local:course-a', 'MATH 10', 'Course');
        $js15 = $this->mockItem('course-recv', 'local:course-recv', 'JS 15', 'Course');

        $items = [$set, $math10, $js15];
        $associations = [
            $this->mockPartOf('course-a', 'set-1'),
            $this->mockArticulation('set-1', 'course-recv', 'key-1'),
        ];

        $service = $this->createService($items, $associations);

        $payload = $service->evaluateDocument(
            $doc,
            $this->institutionMatch('send-org', 'coci:schoolId', '46'),
            $this->institutionMatch('recv-org', 'courseCode', 'SJSU'),
            [],
        );

        $this->assertSame([], $payload['results']);
    }

    public function testEvaluateDocumentOmitsUnsatisfiedArticulations(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');

        $set = $this->mockItem('set-1', 'local:set-1', null, 'Requirement Set');
        $math10 = $this->mockItem('course-a', 'local:course-a', 'MATH 10', 'Course');
        $js15 = $this->mockItem('course-recv', 'local:course-recv', 'JS 15', 'Course');

        $items = [$set, $math10, $js15];
        $associations = [
            $this->mockPartOf('course-a', 'set-1'),
            $this->mockArticulation('set-1', 'course-recv', 'key-1'),
        ];

        $service = $this->createService($items, $associations);

        $sendingCourses = [
            EntityIdentifiers::fromArray([
                ['type' => 'courseCode', 'value' => 'CHEM 1A'],
            ]),
        ];

        $payload = $service->evaluateDocument(
            $doc,
            $this->institutionMatch('send-org', 'coci:schoolId', '46'),
            $this->institutionMatch('recv-org', 'courseCode', 'SJSU'),
            $sendingCourses,
        );

        $this->assertSame([], $payload['results']);
    }

    public function testEvaluateDocumentSkipsArticulationWithBrokenGraph(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');

        $set = $this->mockItem('set-1', 'local:set-1', null, 'Requirement Set');
        $math10 = $this->mockItem('course-a', 'local:course-a', 'MATH 10', 'Course');
        $js15 = $this->mockItem('course-recv', 'local:course-recv', 'JS 15', 'Course');

        $items = [$set, $math10, $js15];
        $associations = [
            $this->mockPartOf('course-a', 'set-1'),
            $this->mockArticulation('set-1', 'course-recv', 'key-valid'),
            $this->mockArticulation('missing-origin', 'course-recv', 'key-broken'),
        ];

        $service = $this->createService($items, $associations);

        $sendingCourses = [
            EntityIdentifiers::fromArray([
                ['type' => 'courseCode', 'value' => 'MATH 10'],
            ]),
        ];

        $payload = $service->evaluateDocument(
            $doc,
            $this->institutionMatch('send-org', 'coci:schoolId', '46'),
            $this->institutionMatch('recv-org', 'courseCode', 'SJSU'),
            $sendingCourses,
        );

        $this->assertCount(1, $payload['results']);
        $this->assertSame('key-valid', $payload['results'][0]['articulationKey']);
    }

    public function testInstitutionResponseOmitsDuplicateIdentifierTypeWhenMatchedOnIdentifier(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');
        $service = $this->createService([], []);

        $payload = $service->evaluateDocument(
            $doc,
            $this->institutionMatch('send-uuid', 'identifier', 'send-uuid', fullStatement: 'Sending College'),
            $this->institutionMatch('recv-uuid', 'identifier', 'recv-uuid', abbreviatedStatement: 'Receiving U'),
            [],
        );

        $this->assertSame('Sending College', $payload['sendingInstitution']['name']);
        $this->assertSame([
            ['type' => 'identifier', 'value' => 'send-uuid'],
        ], $payload['sendingInstitution']['identifiers']);
        $this->assertSame('Receiving U', $payload['receivingInstitution']['name']);
        $this->assertSame([
            ['type' => 'identifier', 'value' => 'recv-uuid'],
        ], $payload['receivingInstitution']['identifiers']);
    }

    public function testCourseCodeMatchesCatalogCourseReferencedOnlyByAssociationIdentifier(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');

        // Package-local requirement nodes (returned by findBy lsDoc — may be empty of courses)
        $set = $this->mockItem('set-1', 'local:set-1', null, 'Requirement Set');
        $js15 = $this->mockItem('course-recv', 'local:course-recv', 'JS 15', 'Course');

        // Catalog-hosted sending course (NOT in findBy(lsDoc); only via findByIdentifiersAnyDoc)
        $math = $this->mockItem('course-a', 'local:course-a', 'MATH 012.', 'Course');
        $math->method('getDiscriminator')->willReturn(LsItemKind::Course->value);

        $associations = [
            $this->mockPartOf('course-a', 'set-1'),
            $this->mockArticulation('set-1', 'course-recv', 'key-1'),
        ];

        $service = $this->createService(
            packageItems: [$set, $js15],
            associations: $associations,
            associationEndpointItems: [$set, $math, $js15],
        );

        $payload = $service->evaluateDocument(
            $doc,
            $this->institutionMatch('send-org', 'coci:schoolId', '46'),
            $this->institutionMatch('recv', 'identifier', 'recv'),
            [EntityIdentifiers::fromArray([['type' => 'courseCode', 'value' => 'MATH 12']])],
        );

        $this->assertCount(1, $payload['results']);
        $this->assertSame('satisfied', $payload['results'][0]['status']);
        $this->assertTrue($payload['results'][0]['requirement']['members'][0]['match']);
    }

    public function testDuplicateNormalizedCourseCodesAllCountAsMatched(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');

        $set = $this->mockItem('set-1', 'local:set-1', null, 'Requirement Set');
        $chemA = $this->mockItem('chem-a', 'local:chem-a', 'CHEM 012A', 'Course');
        $chemA->method('getDiscriminator')->willReturn(LsItemKind::Course->value);
        $chemB = $this->mockItem('chem-b', 'local:chem-b', 'CHEM 012A', 'Course');
        $chemB->method('getDiscriminator')->willReturn(LsItemKind::Course->value);
        $recv = $this->mockItem('recv', 'local:recv', 'CHEM 1A', 'Course');

        $associations = [
            $this->mockPartOf('chem-a', 'set-1'),
            $this->mockPartOf('chem-b', 'set-1'),
            $this->mockArticulation('set-1', 'recv', 'key-dup'),
        ];

        $service = $this->createService(
            packageItems: [$set, $recv],
            associations: $associations,
            associationEndpointItems: [$set, $chemA, $chemB, $recv],
        );

        $payload = $service->evaluateDocument(
            $doc,
            $this->institutionMatch('send-org', 'coci:schoolId', '46'),
            $this->institutionMatch('recv-org', 'identifier', 'recv-org'),
            [EntityIdentifiers::fromArray([['type' => 'courseCode', 'value' => 'CHEM 12A']])],
        );

        $members = $payload['results'][0]['requirement']['members'];
        $this->assertTrue($members[0]['match']);
        $this->assertTrue($members[1]['match']);
    }

    public function testSatisfiedArticulationIsReturnedWhenReceivingSeriesHasMissingChild(): void
    {
        $doc = $this->mockDoc('doc-1', 'local:doc-1', '2025-2026');

        $set = $this->mockItem('set-1', 'local:set-1', null, 'Requirement Set');
        $math = $this->mockItem('course-a', 'local:course-a', 'MATH 10', 'Course');
        $math->method('getDiscriminator')->willReturn(LsItemKind::Course->value);
        $series = $this->mockItem('series-1', 'local:series-1', 'CHEM 112A+112B', 'Series');

        $associations = [
            $this->mockPartOf('course-a', 'set-1'),
            $this->mockPartOf('missing-child', 'series-1'),
            $this->mockArticulation('set-1', 'series-1', 'key-series'),
        ];

        $service = $this->createService(
            packageItems: [$set, $series],
            associations: $associations,
            associationEndpointItems: [$set, $math, $series],
        );

        $payload = $service->evaluateDocument(
            $doc,
            $this->institutionMatch('send-org', 'coci:schoolId', '46'),
            $this->institutionMatch('recv-org', 'identifier', 'recv-org'),
            [EntityIdentifiers::fromArray([['type' => 'courseCode', 'value' => 'MATH 10']])],
        );

        $this->assertCount(1, $payload['results']);
        $this->assertSame('satisfied', $payload['results'][0]['status']);
        $this->assertArrayHasKey('course', $payload['results'][0]['receiving']);
        $this->assertSame('series-1', $payload['results'][0]['receiving']['course']['identifier']);
    }

    /**
     * @param list<LsItem> $packageItems
     * @param list<LsAssociation> $associations
     * @param list<LsItem>|null $associationEndpointItems
     */
    private function createService(
        array $packageItems,
        array $associations,
        ?array $associationEndpointItems = null,
    ): ArticulationEvaluateService {
        $associationEndpointItems ??= $packageItems;

        $itemRepository = $this->createMock(LsItemRepository::class);
        $itemRepository->method('findBy')->willReturn($packageItems);
        $itemRepository->method('findByIdentifiersAnyDoc')->willReturn($associationEndpointItems);

        $associationRepository = $this->createMock(LsAssociationRepository::class);
        $associationRepository->method('findByLsDocAndTypes')->willReturn($associations);

        $index = new EntityIdentifierIndex();
        $matcher = new IdentifierMatcher(new CourseCodeNormalizer());

        return new ArticulationEvaluateService(
            new RequirementTreeBuilder(),
            new RequirementEvaluator(),
            $itemRepository,
            $associationRepository,
            new SendingCourseMatcher($index, $matcher),
            $this->createMockApi1Uris(),
        );
    }

    private function institutionMatch(
        string $itemIdentifier,
        string $matchedType,
        string $matchedValue,
        ?string $legalName = null,
        ?string $abbreviatedStatement = null,
        ?string $fullStatement = null,
    ): InstitutionMatch {
        $extensions = [];
        if (null !== $legalName) {
            $extensions['sdo:legalName'] = $legalName;
        }

        $item = $this->createMock(LsItem::class);
        $item->method('getIdentifier')->willReturn($itemIdentifier);
        $item->method('getExtensions')->willReturn($extensions);
        $item->method('getAbbreviatedStatement')->willReturn($abbreviatedStatement);
        $item->method('getFullStatement')->willReturn($fullStatement);

        return new InstitutionMatch($item, new Identifier($matchedType, $matchedValue));
    }

    private function createMockApi1Uris(): Api1Uris
    {
        $api1Uris = $this->createMock(Api1Uris::class);
        $api1Uris->method('getUri')->willReturnCallback(
            static function (?IdentifiableInterface $obj): ?string {
                if (null === $obj) {
                    return null;
                }

                $uri = $obj->getUri();
                if (is_string($uri) && str_starts_with($uri, 'local:')) {
                    return 'https://example.test/uri/'.$obj->getIdentifier();
                }

                return $uri;
            },
        );

        return $api1Uris;
    }

    private function mockDoc(string $identifier, string $uri, string $yearCode): LsDoc
    {
        $doc = $this->createMock(LsDoc::class);
        $doc->method('getIdentifier')->willReturn($identifier);
        $doc->method('getUri')->willReturn($uri);
        $doc->method('getExtensions')->willReturn([
            'ais:academicYear' => ['code' => $yearCode],
        ]);

        return $doc;
    }

    private function mockItem(
        string $identifier,
        string $uri,
        ?string $courseCode,
        string $itemType,
        array $extensions = [],
    ): LsItem {
        $item = $this->createMock(LsItem::class);
        $item->method('getIdentifier')->willReturn($identifier);
        $item->method('getUri')->willReturn($uri);
        $item->method('getHumanCodingScheme')->willReturn($courseCode);
        $item->method('getItemTypeTitle')->willReturn($itemType);
        $item->method('getExtensions')->willReturn($extensions);
        $item->method('getDiscriminator')->willReturn(
            'Course' === $itemType ? LsItemKind::Course->value : LsItemKind::Default->value,
        );

        return $item;
    }

    private function mockPartOf(string $originId, string $destinationId): LsAssociation
    {
        $assoc = $this->createMock(LsAssociation::class);
        $assoc->method('getType')->willReturn(LsAssociation::PART_OF);
        $assoc->method('getOriginNodeIdentifier')->willReturn($originId);
        $assoc->method('getDestinationNodeIdentifier')->willReturn($destinationId);

        return $assoc;
    }

    private function mockArticulation(string $originId, string $destinationId, string $articulationKey): LsAssociation
    {
        $assoc = $this->createMock(LsAssociation::class);
        $assoc->method('getType')->willReturn('ext:articulation');
        $assoc->method('getOriginNodeIdentifier')->willReturn($originId);
        $assoc->method('getDestinationNodeIdentifier')->willReturn($destinationId);
        $assoc->method('getExtensions')->willReturn([
            'ais:articulationKey' => $articulationKey,
        ]);

        return $assoc;
    }
}
