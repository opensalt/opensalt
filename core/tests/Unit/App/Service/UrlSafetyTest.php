<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Service;

use App\Service\UrlSafety;
use PHPUnit\Framework\TestCase;

class UrlSafetyTest extends TestCase
{
    private UrlSafety $urlSafety;

    protected function setUp(): void
    {
        $this->urlSafety = new UrlSafety();
    }

    public function testRejectsLocalhost(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('http://localhost/'));
    }

    public function testRejectsMetadataEndpoint(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('http://169.254.169.254/latest/meta-data/'));
    }

    public function testRejectsPrivateIp(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('https://192.168.1.1/admin'));
    }

    public function testRejectsTenDot(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('https://10.0.0.1/secret'));
    }

    public function testRejects172Dot16(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('https://172.16.0.1/secret'));
    }

    public function testRejectsFileScheme(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('file:///etc/passwd'));
    }

    public function testRejectsFtpScheme(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('ftp://example.com/file'));
    }

    public function testRejectsHttpScheme(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('http://example.com/file'));
    }

    public function testAllowsPublicHttps(): void
    {
        $this->assertTrue($this->urlSafety->isSafe('https://example.com/case.json'));
    }

    public function testAllowsPublicHttpsPath(): void
    {
        $this->assertTrue($this->urlSafety->isSafe('https://example.com/path/to/resource.json'));
    }

    public function testRejectsNoScheme(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('example.com/path'));
    }

    public function testRejectsEmptyUrl(): void
    {
        $this->assertFalse($this->urlSafety->isSafe(''));
    }

    public function testRejectsIpv6Loopback(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('https://[::1]/'));
    }

    public function testRejectsIpv6LinkLocal(): void
    {
        $this->assertFalse($this->urlSafety->isSafe('https://[fe80::1]/'));
    }
}
