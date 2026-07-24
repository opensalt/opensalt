<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Service\RequirementTreeBuilder;
use PHPUnit\Framework\TestCase;

final class RequirementTreeBuilderTest extends TestCase
{
    public function testRequirementSetBuildsAnyOfWithCourseChildren(): void
    {
        $builder = new RequirementTreeBuilder();
        $itemsById = [
            'set-1' => [
                'identifier' => 'set-1',
                'uri' => 'local:set-1',
                'courseCode' => null,
                'itemType' => 'Requirement Set',
                'extensions' => [],
            ],
            'course-a' => [
                'identifier' => 'course-a',
                'uri' => 'local:course-a',
                'courseCode' => 'MATH 10',
                'itemType' => 'Course',
                'extensions' => [],
            ],
            'course-b' => [
                'identifier' => 'course-b',
                'uri' => 'local:course-b',
                'courseCode' => 'SOC 7',
                'itemType' => 'Course',
                'extensions' => [],
            ],
        ];
        $partOfChildrenByParentId = [
            'set-1' => ['course-a', 'course-b'],
        ];

        $tree = $builder->buildRequirement('set-1', $itemsById, $partOfChildrenByParentId);

        $this->assertSame('anyOf', $tree['op']);
        $this->assertCount(2, $tree['members']);
        $this->assertSame('MATH 10', $tree['members'][0]['courseCode']);
        $this->assertSame('course-a', $tree['members'][0]['identifier']);
        $this->assertSame('SOC 7', $tree['members'][1]['courseCode']);
    }

    public function testRequirementGroupBuildsAllOfWithMembers(): void
    {
        $builder = new RequirementTreeBuilder();
        $itemsById = [
            'group-1' => [
                'identifier' => 'group-1',
                'uri' => 'local:group-1',
                'courseCode' => null,
                'itemType' => 'Requirement Group',
                'extensions' => [],
            ],
            'course-a' => [
                'identifier' => 'course-a',
                'uri' => 'local:course-a',
                'courseCode' => 'CHEM 12A',
                'itemType' => 'Course',
                'extensions' => [],
            ],
            'course-b' => [
                'identifier' => 'course-b',
                'uri' => 'local:course-b',
                'courseCode' => 'CHEM 12B',
                'itemType' => 'Course',
                'extensions' => [],
            ],
        ];
        $partOfChildrenByParentId = [
            'group-1' => ['course-a', 'course-b'],
        ];

        $tree = $builder->buildRequirement('group-1', $itemsById, $partOfChildrenByParentId);

        $this->assertSame('allOf', $tree['op']);
        $this->assertCount(2, $tree['members']);
    }

    public function testCourseBuildsLeafRequirement(): void
    {
        $builder = new RequirementTreeBuilder();
        $itemsById = [
            'course-a' => [
                'identifier' => 'course-a',
                'uri' => 'local:course-a',
                'courseCode' => 'MATH 10',
                'itemType' => 'Course',
                'extensions' => [],
            ],
        ];

        $tree = $builder->buildRequirement('course-a', $itemsById, []);

        $this->assertArrayNotHasKey('op', $tree);
        $this->assertSame('MATH 10', $tree['courseCode']);
        $this->assertSame('course-a', $tree['identifier']);
    }

    public function testSeriesDestinationBuildsReceivingSeriesWithCourses(): void
    {
        $builder = new RequirementTreeBuilder();
        $itemsById = [
            'series-1' => [
                'identifier' => 'series-1',
                'uri' => 'local:series-1',
                'courseCode' => 'CHEM 112A+112B',
                'itemType' => 'Series',
                'extensions' => [],
            ],
            'course-1' => [
                'identifier' => 'course-1',
                'uri' => 'local:course-1',
                'courseCode' => 'CHEM 112A',
                'itemType' => 'Course',
                'extensions' => [],
            ],
            'course-2' => [
                'identifier' => 'course-2',
                'uri' => 'local:course-2',
                'courseCode' => 'CHEM 112B',
                'itemType' => 'Course',
                'extensions' => [],
            ],
        ];
        $partOfChildrenByParentId = [
            'series-1' => ['course-1', 'course-2'],
        ];

        $receiving = $builder->buildReceiving('series-1', $itemsById, $partOfChildrenByParentId);

        $this->assertArrayHasKey('series', $receiving);
        $this->assertSame('and', $receiving['series']['conjunction']);
        $this->assertSame('CHEM 112A+112B', $receiving['series']['courseCode']);
        $this->assertSame('series-1', $receiving['series']['identifier']);
        $this->assertCount(2, $receiving['series']['courses']);
        $this->assertSame('CHEM 112A', $receiving['series']['courses'][0]['courseCode']);
    }

    public function testCourseDestinationBuildsReceivingCourse(): void
    {
        $builder = new RequirementTreeBuilder();
        $itemsById = [
            'course-1' => [
                'identifier' => 'course-1',
                'uri' => 'local:course-1',
                'courseCode' => 'JS 15',
                'itemType' => 'Course',
                'extensions' => [],
            ],
        ];

        $receiving = $builder->buildReceiving('course-1', $itemsById, []);

        $this->assertArrayHasKey('course', $receiving);
        $this->assertSame('JS 15', $receiving['course']['courseCode']);
        $this->assertSame('course-1', $receiving['course']['identifier']);
    }

    public function testBuildRequirementThrowsOnCycle(): void
    {
        $builder = new RequirementTreeBuilder();
        $itemsById = [
            'group-1' => [
                'identifier' => 'group-1',
                'uri' => 'local:group-1',
                'courseCode' => null,
                'itemType' => 'Requirement Group',
                'extensions' => [],
            ],
            'group-2' => [
                'identifier' => 'group-2',
                'uri' => 'local:group-2',
                'courseCode' => null,
                'itemType' => 'Requirement Group',
                'extensions' => [],
            ],
        ];
        $partOfChildrenByParentId = [
            'group-1' => ['group-2'],
            'group-2' => ['group-1'],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cycle or depth limit');

        $builder->buildRequirement('group-1', $itemsById, $partOfChildrenByParentId);
    }
}
