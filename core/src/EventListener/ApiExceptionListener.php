<?php

declare(strict_types=1);

namespace App\EventListener;

use App\DTO\Api1\ImsxCodeMinor;
use App\DTO\Api1\ImsxCodeMinorField;
use App\DTO\Api1\ImsxStatusInfo;
use App\Service\LoggerTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class ApiExceptionListener implements EventSubscriberInterface
{
    use LoggerTrait;

    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    #[\Override]
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

        if (!str_starts_with($request->getPathInfo(), '/ims/case/v1p0/')) {
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
            Response::HTTP_NOT_FOUND
        );
        $response->setStatusCode(Response::HTTP_NOT_FOUND, $errText); // Add error text for IMS Global Compliance Test Suite

        $response->setMaxAge(60);
        $response->setSharedMaxAge(60);
        $response->setPublic();

        return $response;
    }

    protected function isUuidValid(string $uuid): bool
    {
        return Uuid::isValid($uuid);
    }
}
