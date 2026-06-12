<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Attribute\ReadOnlySession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ReadOnlySessionSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        // Listen to CONTROLLER event so we have access to the target method
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $controller = $event->getController();

        // Handle array-based controllers [ControllerClass, 'methodName']
        if (!is_array($controller)) {
            return;
        }

        [$controllerClass, $methodName] = $controller;

        // Check if the attribute exists on the class or the specific method
        if (!$this->hasReadOnlyAttribute($controllerClass, $methodName)) {
            return;
        }

        $request = $event->getRequest();

        // Access the session safely without triggering an autostart exception
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if ($session->isStarted()) {
            $session->save();
        }
    }

    private function hasReadOnlyAttribute(object $controller, string $method): bool
    {
        // Check method attributes
        $reflectionMethod = new \ReflectionMethod($controller, $method);
        if (!empty($reflectionMethod->getAttributes(ReadOnlySession::class))) {
            return true;
        }

        // Check class level attributes
        $reflectionClass = new \ReflectionClass($controller);
        if (!empty($reflectionClass->getAttributes(ReadOnlySession::class))) {
            return true;
        }

        return false;
    }
}
