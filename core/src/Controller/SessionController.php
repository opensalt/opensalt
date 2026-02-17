<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SessionRepository;
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
	    ) {
	}
	
	#[Route(path: '/session/check', name: 'session_check', stateless: true)]
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
		
		// 		Check if the user is actually authenticated (not just an anonymous session)
		        $user = $this->getUser();
		$isAuthenticated = $user !== null;
		
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
