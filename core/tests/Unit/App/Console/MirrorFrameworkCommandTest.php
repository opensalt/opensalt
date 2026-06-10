<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Console;

use App\Command\CommandDispatcherTrait;
use App\Console\Mirror\MirrorFrameworkCommand;
use App\Event\CommandEvent;
use App\Service\MirrorFramework;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MirrorFrameworkCommandTest extends TestCase
{
    private MirrorFramework&MockObject $mirrorFramework;
    private EventDispatcherInterface&MockObject $dispatcher;
    private MirrorFrameworkCommand $command;

    protected function setUp(): void
    {
        $this->mirrorFramework = $this->createMock(MirrorFramework::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->command = new MirrorFrameworkCommand($this->mirrorFramework);
        $this->command->setDispatcher($this->dispatcher);
    }

    public function testMirrorsFrameworkWithDirectPackage(): void
    {
        $json = json_encode(['CFDocument' => ['identifier' => 'doc-1'], 'CFItems' => []]);
        $this->mirrorFramework->method('fetchFramework')
            ->willReturn($json);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CommandEvent::class));

        $io = $this->createMock(SymfonyStyle::class);
        $result = ($this->command)($io, 'https://example.com/framework');

        $this->assertSame(0, $result);
    }

    public function testFollowsPackageUriRedirect(): void
    {
        $redirectJson = json_encode(['CFPackageURI' => ['uri' => 'https://example.com/package']]);
        $fullJson = json_encode(['CFDocument' => ['identifier' => 'doc-1'], 'CFItems' => []]);

        $this->mirrorFramework->method('fetchFramework')
            ->willReturnOnConsecutiveCalls($redirectJson, $fullJson);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CommandEvent::class));

        $io = $this->createMock(SymfonyStyle::class);
        $result = ($this->command)($io, 'https://example.com/framework');

        $this->assertSame(0, $result);
    }

    public function testHandlesFetchError(): void
    {
        $this->mirrorFramework->method('fetchFramework')
            ->willThrowException(new \RuntimeException('Fetch failed'));

        $io = $this->createMock(SymfonyStyle::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fetch failed');

        ($this->command)($io, 'https://example.com/framework');
    }
}
