<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\System\ApiUsageLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiUsageLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Only log if an Authorization header with a Bearer token is present
        $auth = $request->headers->get('Authorization');
        if (null === $auth || !str_starts_with($auth, 'Bearer ')) {
            return;
        }

        // Try to resolve from authenticator (valid token case)
        $userIdentifier = $request->attributes->getString('_api_token_user_identifier');
        try {
            $apiTokenId = $request->attributes->getInt('_api_token_id');
        } catch (\Throwable) {
            $apiTokenId = null;
        }

        // If we do not have a user identifier, skip logging as it's not an API token we recognize.
        if ('' === $userIdentifier) {
            return;
        }

        $response = $event->getResponse();

        $method = $request->getMethod();
        $pathWithQuery = $request->getRequestUri(); // includes path + query string
        $endpointUrl = $request->getUri(); // absolute URL
        $ip = $request->getClientIp();
        $statusCode = $response->getStatusCode();

        // Construct a concise "request that was made"
        $requestSummary = sprintf('%s %s', $method, $pathWithQuery);

        // Build a full HTTP-like request dump (start line + headers + body), truncated to fit DB TEXT safely
        $protocol = (string) ($request->server->get('SERVER_PROTOCOL') ?? 'HTTP/1.1');
        $startLine = sprintf('%s %s %s', $method, $pathWithQuery, $protocol);

        $headerLines = [];
        foreach ($request->headers->all() as $hName => $values) {
            $valueOut = implode(', ', (array) $values);
            if (0 === strcasecmp($hName, 'authorization')) {
                // Redact sensitive token material
                $valueOut = 'Bearer **redacted**';
            }
            $headerLines[] = sprintf('%s: %s', $hName, $valueOut);
        }
        $headersText = implode("\n", $headerLines);

        $body = (string) $request->getContent();
        /*
        $truncated = false;
        $maxLen = 60000; // TEXT is up to ~64KB; leave headroom
        if (null !== $body && strlen($body) > $maxLen) {
            $body = substr($body, 0, $maxLen);
            $truncated = true;
        }
        */

        $requestFull = [
            'request' => $startLine,
            'headers' => $headersText,
            // 'body' => $body.($truncated ? "\n\n-- [truncated] --" : ''),
            'body' => $body,
        ];

        $log = new ApiUsageLog(
            userIdentifier: $userIdentifier,
            apiTokenId: $apiTokenId,
            ip: $ip,
            method: $method,
            request: $requestSummary,
            endpointUrl: $endpointUrl,
            statusCode: $statusCode,
            requestFull: $requestFull,
        );

        // Persist asynchronously at end of request
        // Using EM directly (no flush on kernel terminate here since we're in response already)
        $this->em->persist($log);
        $this->em->flush();
    }
}
