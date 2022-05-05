<?php

namespace App\Controller;

use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SessionController extends AbstractController
{
    /**
     * @var int
     */
    private $sessionMaxIdleTime;

    public function __construct(int $sessionMaxIdleTime = 3600)
    {
        $this->sessionMaxIdleTime = $sessionMaxIdleTime;
    }

    /**
     * @Route("/session/check")
     */
    public function currentSession(Request $request, SessionRepository $repo): JsonResponse
    {
        if (null === ($sessionId = $request->cookies->get('session'))) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        if (null === ($session = $repo->findSession($sessionId))) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        if (0 > ($remainingTime = $this->sessionMaxIdleTime - (time() - $session->getLastUsed()))) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'remainingTime' => $remainingTime,
        ]);
    }

    /**
     * @Route("/session/renew")
     */
    public function renewSession(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'OK',
        ]);
    }
}
