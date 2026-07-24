<?php

declare(strict_types=1);

namespace Tests\Unit\App\Articulations\Service;

use App\Articulations\Service\RequirementEvaluator;
use PHPUnit\Framework\TestCase;

final class RequirementEvaluatorTest extends TestCase
{
    public function testAnyOfSatisfiedWhenOneLeafMatches(): void
    {
        $evaluator = new RequirementEvaluator();
        $tree = [
            'op' => 'anyOf',
            'members' => [
                ['courseCode' => 'MATH 10', 'identifier' => 'a', 'uri' => 'local:a'],
                ['courseCode' => 'SOC 7', 'identifier' => 'b', 'uri' => 'local:b'],
            ],
        ];
        $matched = ['a' => true];

        $out = $evaluator->evaluate($tree, static fn (array $leaf): bool => isset($matched[$leaf['identifier']]));

        $this->assertSame('satisfied', $out['status']);
        $this->assertTrue($out['members'][0]['match']);
        $this->assertFalse($out['members'][1]['match']);
    }

    public function testAllOfPartialWhenSomeLeavesMatch(): void
    {
        $evaluator = new RequirementEvaluator();
        $tree = [
            'op' => 'allOf',
            'members' => [
                ['courseCode' => 'CHEM 12A', 'identifier' => 'a', 'uri' => 'local:a'],
                ['courseCode' => 'CHEM 12B', 'identifier' => 'b', 'uri' => 'local:b'],
            ],
        ];
        $matched = ['a' => true];

        $out = $evaluator->evaluate($tree, static fn (array $leaf): bool => isset($matched[$leaf['identifier']]));

        $this->assertSame('partial', $out['status']);
    }

    public function testPathsCartesianProductAndTruncate(): void
    {
        $evaluator = new RequirementEvaluator();
        $evaluated = [
            'op' => 'allOf',
            'status' => 'unsatisfied',
            'members' => [
                [
                    'op' => 'anyOf',
                    'status' => 'unsatisfied',
                    'members' => [
                        ['courseCode' => 'A1', 'identifier' => '1', 'uri' => 'u1', 'match' => false],
                        ['courseCode' => 'A2', 'identifier' => '2', 'uri' => 'u2', 'match' => false],
                    ],
                ],
                [
                    'op' => 'anyOf',
                    'status' => 'unsatisfied',
                    'members' => [
                        ['courseCode' => 'B1', 'identifier' => '3', 'uri' => 'u3', 'match' => false],
                        ['courseCode' => 'B2', 'identifier' => '4', 'uri' => 'u4', 'match' => false],
                    ],
                ],
            ],
        ];

        $paths = $evaluator->paths($evaluated, 3);

        $this->assertTrue($paths['truncated']);
        $this->assertCount(3, $paths['options']);
    }
}
