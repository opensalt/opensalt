<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Console;

use App\Console\Mirror\MirrorJobCommand;
use App\Entity\Framework\Mirror\Framework;
use App\Service\MirrorFramework;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

class MirrorJobCommandTest extends TestCase
{
    private MirrorFramework&MockObject $mirrorFramework;
    private SymfonyStyle&MockObject $io;
    private MirrorJobCommand $command;

    protected function setUp(): void
    {
        $this->mirrorFramework = $this->createMock(MirrorFramework::class);
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->command = new MirrorJobCommand($this->mirrorFramework);
    }

    public function testReturnsSuccessWhenNoFrameworkToMirror(): void
    {
        $this->mirrorFramework->method('mirrorNext')->willReturn(null);

        $expectedCalls = ['Find and process next mirror job', 'Nothing to do'];
        $this->io->expects(self::exactly(2))
            ->method('comment')
            ->with(self::callback(static function (string $msg) use (&$expectedCalls): bool {
                $expected = array_shift($expectedCalls);
                return $expected === $msg;
            }));

        $result = ($this->command)($this->io);

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testReturnsSuccessWhenFrameworkMirrored(): void
    {
        $framework = $this->createMock(Framework::class);
        $framework->method('getIdentifier')->willReturn('test-id');

        $this->mirrorFramework->method('mirrorNext')->willReturn($framework);

        $this->io->expects(self::once())
            ->method('comment')
            ->with('Find and process next mirror job');

        $this->io->expects(self::once())
            ->method('success')
            ->with('Updated test-id');

        $result = ($this->command)($this->io);

        self::assertSame(Command::SUCCESS, $result);
    }
}
