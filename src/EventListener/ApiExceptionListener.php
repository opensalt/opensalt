<?php

namespace App\EventListener;

use App\DTO\Api1\ImsxCodeMinor;
use App\DTO\Api1\ImsxCodeMinorField;
use App\DTO\Api1\ImsxStatusInfo;
use App\Service\LoggerTrait;
use JMS\Serializer\SerializerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionListener implements EventSubscriberInterface
{
    use LoggerTrait;

    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!($exception instanceof NotFoundHttpException)) {
            return;
        }

        $request = $event->getRequest();
        if ('json' !== $request->getRequestFormat()) {
            return;
        }

        if (0 !== strpos($request->getPathInfo(), '/ims/case/v1p0/')) {
            return;
        }

        $event->setResponse($this->generate404($request->attributes->get('id'), $request->getRequestFormat('json')));
    }

    protected function generate404(string $identifier, string $_format): Response
    {
        // Object not found
        if ($this->isUuidValid($identifier)) {
            $errField = new ImsxCodeMinorField('sourcedId', ImsxCodeMinorField::CODE_MINOR_UNKNOWN_OBJECT);
            $errText = 'Not Found';
        } else {
            $errField = new ImsxCodeMinorField('sourcedId', ImsxCodeMinorField::CODE_MINOR_INVALID_UUID);
            $errText = 'Invalid UUID';
        }
        $errMinor = new ImsxCodeMinor([$errField]);
        $err = new ImsxStatusInfo(
            ImsxStatusInfo::CODE_MAJOR_FAILURE,
            ImsxStatusInfo::SEVERITY_ERROR,
            $errMinor
        );

        $this->info('CASE API: Not Found', ['identifier' => $identifier]);

        $response = new Response(
            $this->serializer->serialize($err, $_format),
            404
        );
        $response->setStatusCode(404, $errText); // Add error text for IMS Global Compliance Test Suite

        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();

        return $response;
    }

    protected function isUuidValid(string $uuid): bool
    {
        if (!Uuid::isValid($uuid)) {
            return false;
        }

        if (!preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[12345][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}/', $uuid)) {
            // Only allow Variant 1 UUIDs for CASE Compliance test
            return false;
        }

        return true;
    }
}
