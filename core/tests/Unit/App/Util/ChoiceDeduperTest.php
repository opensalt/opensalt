<?php

declare(strict_types=1);

namespace Tests\Unit\App\Util;

use App\Entity\Framework\LsDefSubject;
use App\Util\ChoiceDeduper;
use PHPUnit\Framework\TestCase;

class ChoiceDeduperTest extends TestCase
{
    public function testOnePerLabelDeduplicatesAndSortsByLabel(): void
    {
        $rows = [
            $this->subject('Beta', 2),
            $this->subject('alpha', 1),
            $this->subject('alpha', 3),
            $this->subject('Gamma', 4),
        ];

        $result = ChoiceDeduper::onePerLabel(
            $rows,
            [],
            static fn (LsDefSubject $subject): ?string => $subject->getTitle(),
        );

        $labels = array_map(static fn (LsDefSubject $subject): ?string => $subject->getTitle(), $result);
        self::assertSame(['alpha', 'Beta', 'Gamma'], $labels);
        self::assertSame(1, $result[0]->getId(), 'the first-seen duplicate row should be kept');
    }

    public function testLabelsDifferingOnlyByWhitespaceOrCaseAreDuplicates(): void
    {
        $rows = [
            $this->subject('Core Practice', 1),
            $this->subject(' Core Practice ', 2),
            $this->subject('CORE PRACTICE', 3),
            $this->subject("Core  \n Practice", 4),
        ];

        $result = ChoiceDeduper::onePerLabel(
            $rows,
            [],
            static fn (LsDefSubject $subject): ?string => $subject->getTitle(),
        );

        self::assertCount(1, $result);
        self::assertSame(1, $result[0]->getId());
    }

    public function testPreferredRowWinsOverFirstSeen(): void
    {
        $inUse = $this->subject('Science', 10);
        $rows = [
            $this->subject('Science', 1),
            $inUse,
        ];

        $result = ChoiceDeduper::onePerLabel(
            $rows,
            [$inUse],
            static fn (LsDefSubject $subject): ?string => $subject->getTitle(),
        );

        self::assertCount(1, $result);
        self::assertSame(10, $result[0]->getId(), 'a row referenced by the form data must stay selectable');
    }

    public function testNullPreferredEntriesAreIgnored(): void
    {
        $rows = [
            $this->subject('X', 1),
            $this->subject('X', 2),
        ];

        $result = ChoiceDeduper::onePerLabel(
            $rows,
            [null],
            static fn (LsDefSubject $subject): ?string => $subject->getTitle(),
        );

        self::assertCount(1, $result);
        self::assertSame(1, $result[0]->getId());
    }

    private function subject(string $title, ?int $id): LsDefSubject
    {
        $subject = new LsDefSubject();
        $subject->setTitle($title);

        $property = new \ReflectionProperty(LsDefSubject::class, 'id');
        $property->setValue($subject, $id);

        return $subject;
    }
}
