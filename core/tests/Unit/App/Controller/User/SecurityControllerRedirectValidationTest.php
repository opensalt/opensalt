<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Controller\User;

use PHPUnit\Framework\TestCase;

class SecurityControllerRedirectValidationTest extends TestCase
{
    public function testRelativePathIsValid(): void
    {
        $this->assertTrue($this->isSameHostUrl('/dashboard', 'example.com'));
    }

    public function testRelativePathWithQueryIsValid(): void
    {
        $this->assertTrue($this->isSameHostUrl('/framework/123?edit=1', 'example.com'));
    }

    public function testSameHostAbsoluteUrlIsValid(): void
    {
        $this->assertTrue($this->isSameHostUrl('https://example.com/dashboard', 'example.com'));
    }

    public function testDifferentHostAbsoluteUrlIsInvalid(): void
    {
        $this->assertFalse($this->isSameHostUrl('https://evil.com/phish', 'example.com'));
    }

    public function testEmptyUrlIsInvalid(): void
    {
        $this->assertFalse($this->isSameHostUrl('', 'example.com'));
    }

    public function testJavascriptSchemeIsInvalid(): void
    {
        $this->assertFalse($this->isSameHostUrl('javascript:alert(1)', 'example.com'));
    }

    public function testDataSchemeIsInvalid(): void
    {
        $this->assertFalse($this->isSameHostUrl('data:text/html,<script>alert(1)</script>', 'example.com'));
    }

    public function testFileSchemeIsInvalid(): void
    {
        $this->assertFalse($this->isSameHostUrl('file:///etc/passwd', 'example.com'));
    }

    private function isSameHostUrl(string $url, string $host): bool
    {
        if ('' === $url) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $parsed = parse_url($url);
        if (!isset($parsed['host']) || $parsed['host'] !== $host) {
            return false;
        }

        $scheme = $parsed['scheme'] ?? '';
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return true;
    }
}
