<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Session;

use App\Security\Session\SessionIdleHandlerEventSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SessionIdleHandlerEventSubscriberTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TokenStorageInterface&MockObject $tokenStorage;
    private HttpKernelInterface&MockObject $kernel;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    public function testSubscribesToKernelRequest(): void
    {
        $events = SessionIdleHandlerEventSubscriber::getSubscribedEvents();

        $this->assertSame([KernelEvents::REQUEST => 'onKernelRequest'], $events);
    }

    public function testSkipsSubRequest(): void
    {
        $subscriber = new SessionIdleHandlerEventSubscriber(
            $this->authorizationChecker,
            $this->tokenStorage,
            600,
        );

        $this->tokenStorage->expects($this->never())->method('setToken');

        $request = new Request();
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelRequest($event);
    }

    public function testSkipsWhenNoSecurityToken(): void
    {
        $subscriber = new SessionIdleHandlerEventSubscriber(
            $this->authorizationChecker,
            $this->tokenStorage,
            600,
        );

        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->authorizationChecker->expects($this->never())->method('isGranted');
        $this->tokenStorage->expects($this->never())->method('setToken');

        $request = new Request();
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);
    }

    public function testSkipsWhenNotFullyAuthenticated(): void
    {
        $subscriber = new SessionIdleHandlerEventSubscriber(
            $this->authorizationChecker,
            $this->tokenStorage,
            600,
        );

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->authorizationChecker->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(false);
        $this->tokenStorage->expects($this->never())->method('setToken');

        $request = new Request();
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);
    }

    public function testSkipsWhenIdleTimeZero(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->authorizationChecker->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $this->tokenStorage->expects($this->never())->method('setToken');

        $subscriber = new SessionIdleHandlerEventSubscriber(
            $this->authorizationChecker,
            $this->tokenStorage,
            0,
        );

        $request = new Request();
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);
    }

    public function testInvalidatesSessionOnIdleTimeout(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->authorizationChecker->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $this->tokenStorage->expects($this->once())->method('setToken')->with(null);

        $metadataBag = $this->createMock(MetadataBag::class);
        $metadataBag->method('getLastUsed')->willReturn(time() - 700);

        $flashBag = $this->createMock(FlashBagInterface::class);

        $session = $this->createMock(Session::class);
        $session->method('getMetadataBag')->willReturn($metadataBag);
        $session->expects($this->once())->method('start');
        $session->expects($this->once())->method('invalidate');
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = new Request();
        $request->setSession($session);

        $subscriber = new SessionIdleHandlerEventSubscriber(
            $this->authorizationChecker,
            $this->tokenStorage,
            600,
        );

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You have been logged out due to inactivity.');

        $subscriber->onKernelRequest($event);
    }

    public function testDoesNotInvalidateWhenWithinIdleTime(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->authorizationChecker->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $this->tokenStorage->expects($this->never())->method('setToken');

        $metadataBag = $this->createMock(MetadataBag::class);
        $metadataBag->method('getLastUsed')->willReturn(time() - 100);

        $session = $this->createMock(Session::class);
        $session->method('getMetadataBag')->willReturn($metadataBag);
        $session->expects($this->once())->method('start');
        $session->expects($this->never())->method('invalidate');

        $request = new Request();
        $request->setSession($session);

        $subscriber = new SessionIdleHandlerEventSubscriber(
            $this->authorizationChecker,
            $this->tokenStorage,
            600,
        );

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);
    }

    public function testSetsFlashOnTimeout(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->authorizationChecker->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $this->tokenStorage->expects($this->once())->method('setToken')->with(null);

        $metadataBag = $this->createMock(MetadataBag::class);
        $metadataBag->method('getLastUsed')->willReturn(time() - 700);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())->method('set')->with('warning', 'You have been logged out due to inactivity.');

        $session = $this->createMock(Session::class);
        $session->method('getMetadataBag')->willReturn($metadataBag);
        $session->method('getFlashBag')->willReturn($flashBag);
        $session->expects($this->once())->method('start');
        $session->expects($this->once())->method('invalidate');

        $request = new Request();
        $request->setSession($session);

        $subscriber = new SessionIdleHandlerEventSubscriber(
            $this->authorizationChecker,
            $this->tokenStorage,
            600,
        );

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You have been logged out due to inactivity.');

        $subscriber->onKernelRequest($event);
    }
}
