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
    /**
     * Headers whose values should be redacted from logs.
     * Configurable via constructor for deployment-specific tuning.
     */
    private const SENSITIVE_HEADERS = [
        'authorization',
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
        'x-api-key',
    ];

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
        $pathWithQuery = $request->getRequestUri();
        $endpointUrl = $request->getUri();
        $ip = $request->getClientIp();
        $statusCode = $response->getStatusCode();

        $requestSummary = sprintf('%s %s', $method, $pathWithQuery);

        $protocol = (string) ($request->server->get('SERVER_PROTOCOL') ?? 'HTTP/1.1');
        $startLine = sprintf('%s %s %s', $method, $pathWithQuery, $protocol);

        // Build headers, redacting all sensitive ones
        $headerLines = [];
        foreach ($request->headers->all() as $hName => $values) {
            $valueOut = implode(', ', (array) $values);
            if (in_array(strtolower($hName), self::SENSITIVE_HEADERS, true)) {
                $valueOut = '**redacted**';
            }
            $headerLines[] = sprintf('%s: %s', $hName, $valueOut);
        }
        $headersText = implode("\n", $headerLines);

        // Build body with truncation and sensitive-field masking
        $body = (string) $request->getContent();
        $truncated = false;
        $maxLen = 60000; // TEXT is up to ~64KB; leave headroom
        if (strlen($body) > $maxLen) {
            $body = substr($body, 0, $maxLen);
            $truncated = true;
        }
        // Mask sensitive fields in JSON bodies
        $body = preg_replace(
            '/"((?:password|secret|token|csrf|api[_-]?key)[^"]*)"\s*:\s*"[^"]*"/i',
            '"$1":"**redacted**"',
            $body,
        );

        $requestFull = [
            'request' => $startLine,
            'headers' => $headersText,
            'body' => $body . ($truncated ? "\n\n-- [truncated] --" : ''),
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

        $this->em->persist($log);
        $this->em->flush();
    }
}
