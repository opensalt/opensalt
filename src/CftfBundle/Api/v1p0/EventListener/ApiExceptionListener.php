<?php

namespace CftfBundle\Api\v1p0\EventListener;

use CftfBundle\Api\v1p0\DTO\ImsxCodeMinor;
use CftfBundle\Api\v1p0\DTO\ImsxCodeMinorField;
use CftfBundle\Api\v1p0\DTO\ImsxStatusInfo;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\Serializer\SerializerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ApiExceptionListener
 *
 * @DI\Service()
 */
class ApiExceptionListener
{
    /** @var SerializerInterface */
    private $serializer;

    /**
     * ApiExceptionListener constructor.
     *
     * @param SerializerInterface $serializer
     *
     * @DI\InjectParams({
     *     "serializer" = @DI\Inject("serializer")
     * })
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @DI\Observe(KernelEvents::EXCEPTION)
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();
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

    /**
     * @param string $identifier
     * @param string $_format
     *
     * @return Response
     */
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

    /**
     * Determine if the UUID is valid
     *
     * @param string $uuid
     *
     * @return bool
     */
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
