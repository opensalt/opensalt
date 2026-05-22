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

        // Check if the user is actually authenticated (not just an anonymous session)
        $sessionData = $session->getData();
        $data = $this->decodeSessionData($sessionData);
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

    /**
     * Decode PHP session data format (key|serialized_value).
     *
     * @param string $sessionData The raw session data string
     *
     * @return array<string, mixed> The decoded session data as an array
     */
    private function decodeSessionData(string $sessionData): array
    {
        if (empty($sessionData)) {
            return [];
        }

        $result = [];
        $offset = 0;
        $length = strlen($sessionData);

        while ($offset < $length) {
            // Find the position of the pipe character (key separator)
            $pipePos = strpos($sessionData, '|', $offset);

            if (false === $pipePos) {
                break;
            }

            // Extract the key
            $key = substr($sessionData, $offset, $pipePos - $offset);
            $offset = $pipePos + 1;

            // Now we need to unserialize the value
            // PHP session format uses standard serialize() for values
            // We need to find where the serialized value ends
            $temp = substr($sessionData, $offset);
            $value = @unserialize($temp);

            if (false === $value && 'b:0;' !== substr($temp, 0, 4)) {
                // Failed to unserialize, try to skip this entry
                break;
            }

            $result[$key] = $value;

            // Calculate how many bytes were consumed by serialize()
            // We need to determine the actual length of the serialized string
            $serializedLength = strlen(serialize($value));
            $offset += $serializedLength;
        }

        return $result;
    }
}
