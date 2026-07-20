<?php

namespace App\Mcp;

use Mcp\Server;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtocolVersionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drop-in replacement for the symfony/mcp-bundle McpController.
 *
 * The upstream transport applies a DNS-rebinding host allowlist
 * (localhost/127.0.0.1 only) by default, which rejects the dev/runtime
 * hostname behind the fronting reverse proxy. Host validation is enforced
 * by the proxy/firewall, so we build the transport with a custom middleware
 * stack that keeps CORS + protocol-version negotiation but omits the
 * host allowlist, allowing any hostname.
 */
final class McpController
{
    public function __construct(
        private readonly Server $server,
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function handle(Request $request): Response
    {
        $transport = new StreamableHttpTransport(
            $this->httpMessageFactory->createRequest($request),
            $this->responseFactory,
            $this->streamFactory,
            logger: $this->logger,
            middleware: [
                new CorsMiddleware(),
                new ProtocolVersionMiddleware(),
            ],
        );

        $psrResponse = $this->server->run($transport);
        $streamed = 'text/event-stream' === strtolower($psrResponse->getHeaderLine('Content-Type'));

        return $this->httpFoundationFactory->createResponse($psrResponse, $streamed);
    }
}
