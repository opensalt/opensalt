<?php

namespace App\Util;

class EducationLevelSet
{
    private array $grades;

    public function __construct(array $passedGrades)
    {
        $gradeSets = [[]]; // initialize the array with an empty array inside it
        foreach ($passedGrades as $grade) {
            if (empty($grade) || !is_string($grade)) {
                continue;
            }
            $gradeSets[] = self::convertGradeString($grade);
        }

        $this->grades = array_unique(array_merge(...$gradeSets));
    }

    /**
     * @param array<string>|string|null $passedGrades
     */
    public static function fromStringOrArray(array|string|null $passedGrades): self
    {
        if (null === $passedGrades || is_string($passedGrades)) {
            return self::fromString($passedGrades);
        }

        return self::fromArray($passedGrades);
    }

    public static function fromString(?string $passedGradeString): self
    {
        if (null === $passedGradeString) {
            return new self([]);
        }

        $passedGrades = str_replace(' ', '', $passedGradeString);
        $passedGrades = explode(',', $passedGrades);

        return new self($passedGrades);
    }

    public static function fromArray(array $passedGradeArray): self
    {
        return new self($passedGradeArray);
    }

    public function toString(): ?string
    {
        if (0 === count($this->grades)) {
            return null;
        }

        return implode(',', $this->grades);
    }

    public function toArray(): array
    {
        return $this->grades;
    }

    private static function normalizeGrade(string $grade): string
    {
        if (is_numeric($grade)) {
            return self::normalizeNumericGrade($grade);
        }

        return self::normalizeStringGrade($grade);
    }

    private static function convertGradeString(string $gradeString): array
    {
        if ('OT' === $gradeString) {
            return [$gradeString];
        }

        $grade = self::normalizeGrade($gradeString);
        if ('OT' !== $grade) {
            return [$grade];
        }

        $grades = self::translateGradeString($gradeString);
        if (0 < count($grades)) {
            return $grades;
        }

        if (str_contains($gradeString, '-')) {
            [$lo, $hi] = explode('-', $gradeString, 2);

            $lo = self::normalizeGrade($lo);
            $lo = match ($lo) {
                'PK' => '-1',
                'KG' => '0',
                default => $lo,
            };

            $hi = self::normalizeGrade($hi);
            $hi = match ($hi) {
                'PK' => '-1',
                'KG' => '0',
                default => $hi,
            };

            if (!is_numeric($lo) || !is_numeric($hi) || $hi < $lo) {
                return ['OT'];
            }

            return array_map(static fn ($x): string => self::normalizeGrade((string) $x), range($lo, $hi));
        }

        return ['OT'];
    }

    private static function translateGradeString(string $gradeString): array
    {
        if ('HS' === $gradeString) {
            return [
                '09',
                '10',
                '11',
                '12',
            ];
        }

        return [];
    }

    private static function normalizeNumericGrade(string $gradeString): string
    {
        $grade = (int) $gradeString;

        if ($grade < -1 || $grade > 13 || ((float) $grade !== (float) $gradeString)) {
            return 'OT';
        }

        if (-1 === $grade) {
            return 'PK';
        }

        if (0 === $grade) {
            return 'KG';
        }

        // 01 to 13
        return sprintf('%02d', $grade);
    }

    private static function normalizeStringGrade(string $grade): string
    {
        if ('K' === $grade) {
            return 'KG';
        }

        if (in_array($grade, [
            'IT',
            'PR',
            'PK',
            'TK',
            'KG',
            'AS',
            'BA',
            'PB',
            'MD',
            'PM',
            'DO',
            'PD',
            'AE',
            'PT',
            'OT',
        ], true)) {
            return $grade;
        }

        return 'OT';
    }
}
