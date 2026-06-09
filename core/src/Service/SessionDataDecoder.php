<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final readonly class SessionDataDecoder
{
    /**
     * Parse raw PHP session data (key|serialized_value format) into an array.
     * Uses allowed_classes => false to prevent object instantiation.
     */
    public function decodeSessionData(string $sessionData): array
    {
        if (empty($sessionData)) {
            return [];
        }

        $result = [];
        $offset = 0;
        $length = strlen($sessionData);

        while ($offset < $length) {
            $pipePos = strpos($sessionData, '|', $offset);

            if (false === $pipePos) {
                break;
            }

            $key = substr($sessionData, $offset, $pipePos - $offset);
            $offset = $pipePos + 1;

            $temp = substr($sessionData, $offset);
            $value = @unserialize($temp, ['allowed_classes' => false]);

            if (false === $value && 'b:0;' !== substr($temp, 0, 4)) {
                break;
            }

            $result[$key] = $value;
            $serializedLength = strlen(serialize($value));
            $offset += $serializedLength;
        }

        return $result;
    }

    /**
     * Unserialize a Symfony security token with a strict class allowlist.
     * Only permits known Symfony token types.
     *
     * @throws \InvalidArgumentException if the unserialized value is not a TokenInterface
     */
    public function decodeSecurityToken(string $tokenData): TokenInterface
    {
        $token = unserialize($tokenData, [
            'allowed_classes' => [
                UsernamePasswordToken::class,
                \Symfony\Component\Security\Core\Authentication\Token\RememberMeToken::class,
                \Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken::class,
                \Symfony\Component\Security\Core\User\InMemoryUser::class,
            ],
        ]);

        if (false === $token || !$token instanceof TokenInterface) {
            throw new \InvalidArgumentException('Invalid authentication token in session.');
        }

        return $token;
    }
}
