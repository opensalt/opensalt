<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User\ApiToken;
use App\Repository\User\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class ApiTokenAuthenticator implements AccessTokenHandlerInterface
{
    public function __construct(
        private ApiTokenRepository $apiTokenRepository,
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {
    }

    #[\Override]
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            $id = ApiToken::decodedId($accessToken);
        } catch (\Throwable) {
            throw new AuthenticationException();
        }

        $apiToken = $this->apiTokenRepository->find($id);
        if (null === $apiToken) {
            throw new AuthenticationException();
        }

        if (!$apiToken->verifyToken($accessToken)) {
            throw new AuthenticationException();
        }

        $apiToken->rehashToken($accessToken);

        // Mark this request as authenticated via API token for downstream logging
        $req = $this->requestStack->getCurrentRequest();
        if (null !== $req) {
            $req->attributes->set('_api_token_user_identifier', $apiToken->user->getUserIdentifier());
            $req->attributes->set('_api_token_id', $apiToken->id);
        }

        // Update last-used timestamp
        $apiToken->lastUsed = new \DateTimeImmutable();
        $this->em->flush();

        // Let Security load the User via its identifier
        return new UserBadge($apiToken->user->getUserIdentifier());
    }
}
