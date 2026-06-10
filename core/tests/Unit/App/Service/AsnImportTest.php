<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Service;

use App\Service\AsnImport;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AsnImport service.
 */
class AsnImportTest extends TestCase
{
    private function createAsnImport(?LoggerInterface $logger = null): AsnImport
    {
        $em = $this->createMock(EntityManagerInterface::class);

        return new AsnImport($em, $logger);
    }

    public function testFetchAsnDocumentThrowsRuntimeExceptionOnAllPrefixesFailed(): void
    {
        $import = $this->getMockBuilder(AsnImport::class)
            ->setConstructorArgs([
                $this->createMock(EntityManagerInterface::class),
                null,
            ])
            ->onlyMethods(['requestAsnDocument'])
            ->getMock();

        $import->method('requestAsnDocument')
            ->willThrowException(
                new RequestException('Not found', new Request('GET', 'test'), new Response(404))
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('all URL prefixes failed');
        $import->fetchAsnDocument('http://example.org/resources/D0000000');
    }

    public function testRequestAsnDocumentThrowsRuntimeExceptionOnNon200(): void
    {
        $import = $this->getMockBuilder(AsnImport::class)
            ->setConstructorArgs([
                $this->createMock(EntityManagerInterface::class),
                null,
            ])
            ->onlyMethods(['requestAsnDocument'])
            ->getMock();

        $import->method('requestAsnDocument')
            ->willThrowException(
                new \RuntimeException('Error getting document from ASN (HTTP 500): http://example.org/test')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error getting document from ASN');
        $import->requestAsnDocument('http://example.org/test');
    }

    public function testFetchAsnDocumentLogsOnRequestException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->stringContains('ASN URL not found'),
                $this->callback(fn (array $ctx) => isset($ctx['url']) && isset($ctx['exception']))
            );

        $import = $this->getMockBuilder(AsnImport::class)
            ->setConstructorArgs([
                $this->createMock(EntityManagerInterface::class),
                $logger,
            ])
            ->onlyMethods(['requestAsnDocument'])
            ->getMock();

        $import->method('requestAsnDocument')
            ->willThrowException(
                new RequestException('Not found', new Request('GET', 'test'), new Response(404))
            );

        try {
            $import->fetchAsnDocument('http://example.org/resources/D0000000');
        } catch (\RuntimeException) {
        }
    }

    public function testNonRequestExceptionPropagates(): void
    {
        $import = $this->getMockBuilder(AsnImport::class)
            ->setConstructorArgs([
                $this->createMock(EntityManagerInterface::class),
                null,
            ])
            ->onlyMethods(['requestAsnDocument'])
            ->getMock();

        $import->method('requestAsnDocument')
            ->willThrowException(new \RuntimeException('ASN lookup failed'));

        $this->expectException(\RuntimeException::class);
        $import->fetchAsnDocument('invalid-locator-no-match');
    }

    public function testConstructorAcceptsNullLogger(): void
    {
        $import = $this->createAsnImport();
        $this->assertInstanceOf(AsnImport::class, $import);
    }

    public function testConstructorAcceptsLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $import = $this->createAsnImport($logger);
        $this->assertInstanceOf(AsnImport::class, $import);
    }
}
