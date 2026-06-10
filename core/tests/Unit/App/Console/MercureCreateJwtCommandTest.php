<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Console;

use App\Console\MercureCreateJwtCommand;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

class MercureCreateJwtCommandTest extends TestCase
{
    private SymfonyStyle&MockObject $io;
    private MercureCreateJwtCommand $command;

    protected function setUp(): void
    {
        $this->io = $this->createMock(SymfonyStyle::class);
        $this->command = new MercureCreateJwtCommand();
    }

    public function testReturnsSuccessWithKeyOption(): void
    {
        $this->io->expects(self::once())
            ->method('writeln')
            ->with(self::callback(static fn (string $v): bool => '' !== $v));

        $result = ($this->command)($this->io, key: 'this-is-a-secret-key-long-enough-for-hs256');

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testReturnsFailureWithoutKey(): void
    {
        $this->io->expects(self::once())
            ->method('error')
            ->with('A signing key is required. Use --secret-file=<path> or --key=<value>.');

        $result = ($this->command)($this->io);

        self::assertSame(Command::FAILURE, $result);
    }

    public function testReturnsFailureWithEmptyKey(): void
    {
        $this->io->expects(self::once())
            ->method('error')
            ->with('A signing key is required. Use --secret-file=<path> or --key=<value>.');

        $result = ($this->command)($this->io, null, '');

        self::assertSame(Command::FAILURE, $result);
    }

    public function testReturnsFailureWithUnreadableSecretFile(): void
    {
        $this->io->expects(self::once())
            ->method('error')
            ->with(self::stringContains('Cannot read secret file'));

        $result = ($this->command)($this->io, secretFile: '/nonexistent/file');

        self::assertSame(Command::FAILURE, $result);
    }

    public function testReturnsSuccessWithSecretFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'jwt_test_');
        file_put_contents($tmpFile, 'file-secret-key-that-is-long-enough-for-hs256');

        $this->io->expects(self::once())
            ->method('writeln')
            ->with(self::callback(static fn (string $v): bool => '' !== $v));

        try {
            $result = ($this->command)($this->io, secretFile: $tmpFile);
        } finally {
            @unlink($tmpFile);
        }

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testUsesCustomPayload(): void
    {
        $captured = null;
        $this->io->expects(self::once())
            ->method('writeln')
            ->with(self::callback(function (string $token) use (&$captured): bool {
                $captured = $token;
                return true;
            }));

        $result = ($this->command)(
            $this->io,
            null,
            'this-is-a-secret-key-long-enough-for-hs256',
            '{"mercure":{"publish":["test"]}}',
        );

        self::assertSame(Command::SUCCESS, $result);

        $decoded = JWT::decode($captured, new Key('this-is-a-secret-key-long-enough-for-hs256', 'HS256'));
        self::assertSame(['test'], $decoded->mercure->publish);
    }

    public function testGeneratedTokenIsValidJwt(): void
    {
        $captured = null;
        $this->io->expects(self::once())
            ->method('writeln')
            ->with(self::callback(function (string $token) use (&$captured): bool {
                $captured = $token;
                return true;
            }));

        ($this->command)($this->io, key: 'this-is-a-secret-key-long-enough-for-hs256');

        $decoded = JWT::decode($captured, new Key('this-is-a-secret-key-long-enough-for-hs256', 'HS256'));
        self::assertIsArray($decoded->mercure->publish);
        self::assertContains('*', $decoded->mercure->publish);
    }
}
