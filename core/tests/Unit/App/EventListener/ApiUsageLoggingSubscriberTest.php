<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\EventListener;

use App\Entity\System\ApiUsageLog;
use App\EventListener\ApiUsageLoggingSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiUsageLoggingSubscriberTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ApiUsageLoggingSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->subscriber = new ApiUsageLoggingSubscriber($this->em);
    }

    public function testRedactsAuthorizationHeader(): void
    {
        $capturedLog = null;
        $this->em->method('persist')->willReturnCallback(function ($log) use (&$capturedLog) {
            $capturedLog = $log;
        });

        $event = $this->createResponseEvent([
            'Authorization' => 'Bearer secret-token-123',
            'Accept' => 'application/json',
        ], '', ['_api_token_user_identifier' => 'testuser', '_api_token_id' => 1]);

        $this->subscriber->onKernelResponse($event);

        $this->assertNotNull($capturedLog);
        $headers = $capturedLog->requestFull['headers'];
        $this->assertStringContainsString('authorization: **redacted**', $headers);
        $this->assertStringContainsString('accept: application/json', $headers);
        $this->assertStringNotContainsString('secret-token-123', $headers);
    }

    public function testRedactsCookieHeader(): void
    {
        $capturedLog = null;
        $this->em->method('persist')->willReturnCallback(function ($log) use (&$capturedLog) {
            $capturedLog = $log;
        });

        $event = $this->createResponseEvent([
            'Authorization' => 'Bearer token',
            'Cookie' => 'session=abc123; csrf=xyz',
        ], '', ['_api_token_user_identifier' => 'testuser', '_api_token_id' => 1]);

        $this->subscriber->onKernelResponse($event);

        $headers = $capturedLog->requestFull['headers'];
        $this->assertStringContainsString('cookie: **redacted**', $headers);
    }

    public function testRedactsXCsrfTokenHeader(): void
    {
        $capturedLog = null;
        $this->em->method('persist')->willReturnCallback(function ($log) use (&$capturedLog) {
            $capturedLog = $log;
        });

        $event = $this->createResponseEvent([
            'Authorization' => 'Bearer token',
            'X-CSRF-Token' => 'csrf-value-123',
        ], '', ['_api_token_user_identifier' => 'testuser', '_api_token_id' => 1]);

        $this->subscriber->onKernelResponse($event);

        $headers = $capturedLog->requestFull['headers'];
        $this->assertStringContainsString('x-csrf-token: **redacted**', $headers);
    }

    public function testMasksSensitiveFieldsInBody(): void
    {
        $capturedLog = null;
        $this->em->method('persist')->willReturnCallback(function ($log) use (&$capturedLog) {
            $capturedLog = $log;
        });

        $body = json_encode([
            'username' => 'testuser',
            'password' => 'secret123',
            'csrf_token' => 'abc',
        ]);

        $event = $this->createResponseEvent([
            'Authorization' => 'Bearer token',
        ], $body, ['_api_token_user_identifier' => 'testuser', '_api_token_id' => 1]);

        $this->subscriber->onKernelResponse($event);

        $loggedBody = $capturedLog->requestFull['body'];
        $this->assertStringContainsString('"password":"**redacted**"', $loggedBody);
        $this->assertStringContainsString('"csrf_token":"**redacted**"', $loggedBody);
        $this->assertStringContainsString('"username":"testuser"', $loggedBody);
    }

    public function testTruncatesLargeBody(): void
    {
        $capturedLog = null;
        $this->em->method('persist')->willReturnCallback(function ($log) use (&$capturedLog) {
            $capturedLog = $log;
        });

        $body = str_repeat('x', 70000);

        $event = $this->createResponseEvent([
            'Authorization' => 'Bearer token',
        ], $body, ['_api_token_user_identifier' => 'testuser', '_api_token_id' => 1]);

        $this->subscriber->onKernelResponse($event);

        $loggedBody = $capturedLog->requestFull['body'];
        $this->assertStringContainsString('-- [truncated] --', $loggedBody);
        $this->assertLessThan(70000, strlen($loggedBody));
    }

    public function testSkipsNonBearerRequests(): void
    {
        $this->em->expects($this->never())->method('persist');

        $event = $this->createResponseEvent([
            'Accept' => 'text/html',
        ], '', []);

        $this->subscriber->onKernelResponse($event);
    }

    private function createResponseEvent(
        array $headers,
        string $body,
        array $attributes,
    ): ResponseEvent {
        $request = new Request([], [], $attributes, [], [], [
            'REQUEST_URI' => '/api/test',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ], $body);
        $request->headers->add($headers);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $response = new Response();

        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
