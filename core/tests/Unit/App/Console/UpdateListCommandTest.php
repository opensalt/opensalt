<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Console;

use App\Console\Mirror\UpdateListCommand;
use App\Entity\Framework\Mirror\Server;
use App\Service\MirrorServer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateListCommandTest extends TestCase
{
    private MirrorServer&MockObject $mirrorServer;
    private UpdateListCommand $command;

    protected function setUp(): void
    {
        $this->mirrorServer = $this->createMock(MirrorServer::class);
        $this->command = new UpdateListCommand($this->mirrorServer);
    }

    public function testReturnsSuccessWhenNoServerToProcess(): void
    {
        $this->mirrorServer->method('updateNext')
            ->willReturn(null);

        $io = $this->createMock(SymfonyStyle::class);
        $result = ($this->command)($io);

        $this->assertSame(0, $result);
    }

    public function testReturnsSuccessWhenServerUpdated(): void
    {
        $server = $this->createMock(Server::class);
        $server->method('getUrl')
            ->willReturn('https://example.com');

        $this->mirrorServer->method('updateNext')
            ->willReturn($server);

        $io = $this->createMock(SymfonyStyle::class);
        $result = ($this->command)($io);

        $this->assertSame(0, $result);
    }
}
