<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SessionRepository;
use App\Service\SessionDataDecoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SessionController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'session_max_idle_time')] private readonly int $sessionMaxIdleTime = 3600,
        private readonly SessionDataDecoder $sessionDataDecoder = new SessionDataDecoder(),
    ) {
    }

    #[Route(path: '/session/check', name: 'session_check', stateless: true)]
    public function currentSession(Request $request, SessionRepository $repo): JsonResponse
    {
        if (null === ($sessionId = $request->cookies->get('session'))) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        // Require X-Requested-With header to mitigate session oracle attacks.
        // Cannot use isCsrfTokenValid() here because this route is stateless (no session).
        // The X-Requested-With header is automatically sent by fetch/XMLHttpRequest but
        // cannot be set by cross-origin requests without CORS preflight — providing
        // equivalent protection without server-side state.
        if ('XMLHttpRequest' !== $request->headers->get('X-Requested-With')) {
            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }

        if (null === ($session = $repo->findSession($sessionId))) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        if (0 > ($remainingTime = $this->sessionMaxIdleTime - (time() - $session->getLastUsed()))) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        // Check if the user is actually authenticated (not just an anonymous session)
        $sessionData = $session->getData();
        $data = $this->sessionDataDecoder->decodeSessionData($sessionData);
        $isAuthenticated = isset($data['_sf2_attributes']['_security_main']);

        return new JsonResponse([
            'remainingTime' => $remainingTime,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route(path: '/session/renew')]
    public function renewSession(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'OK',
        ]);
    }
}
