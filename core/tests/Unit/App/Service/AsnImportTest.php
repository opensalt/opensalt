<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Service;

use App\Service\AsnImport;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AsnImport service.
 *
 * These tests make real HTTP requests to RFC 2606 reserved domains (.invalid).
 * They verify error handling when the remote service is unreachable.
 * Exclude from CI with: --exclude-group=network
 *
 * @group network
 */
class AsnImportTest extends TestCase
{
    public function testFetchAsnDocumentThrowsRuntimeExceptionOnAllPrefixesFailed(): void
    {
        $import = new AsnImport();

        $this->expectException(\RuntimeException::class);
        $import->fetchAsnDocument('http://nonexistent.invalid/resources/D0000000');
    }

    public function testRequestAsnDocumentThrowsRuntimeExceptionOnNon200(): void
    {
        $import = new AsnImport();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error getting document from ASN');

        $import->requestAsnDocument('http://nonexistent.invalid/resources/D0000000_full.json');
    }

    public function testFetchAsnDocumentLogsOnRequestException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->stringContains('ASN URL not found'),
                $this->callback(fn (array $context) => isset($context['url']) && isset($context['exception']))
            );

        $import = new AsnImport($logger);

        try {
            $import->fetchAsnDocument('http://nonexistent.invalid/resources/D0000000');
        } catch (\RuntimeException) {
        }
    }

    public function testNonRequestExceptionPropagates(): void
    {
        $import = new AsnImport();

        $thrown = false;
        try {
            $import->fetchAsnDocument('invalid-locator-no-match');
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertStringContainsString('ASN', $e->getMessage());
        }
        $this->assertTrue($thrown, 'Exception should propagate, not be silently caught');
    }

    public function testConstructorAcceptsNullLogger(): void
    {
        $import = new AsnImport();
        $this->assertInstanceOf(AsnImport::class, $import);
    }

    public function testConstructorAcceptsLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $import = new AsnImport($logger);
        $this->assertInstanceOf(AsnImport::class, $import);
    }
}
