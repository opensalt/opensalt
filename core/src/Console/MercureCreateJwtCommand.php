<?php

declare(strict_types=1);

namespace App\Console;

use Firebase\JWT\JWT;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\WebTokenFactory;

#[AsCommand(
    name: 'mercure:create-jwt',
    description: 'Create a JWT to use with Mercure (0.x legacy claim, or RFC 9068 for Mercure 1.0)'
)]
/**
 * Creates a JWT for Mercure hub authentication.
 *
 * Security notes:
 * - Prefer --secret-file over --key to avoid leaking the key via /proc/<pid>/cmdline.
 * - The key material remains in process memory for the lifetime of the command.
 *   PHP does not offer secure memory zeroing, so this is an inherent limitation.
 *
 * Protocols:
 * - 0.x (default): mint a token with the legacy "mercure" claim. Accepted by hubs
 *   running in Mercure 1.0 compatibility mode.
 * - 1.0: mint an RFC 9068 access token ("at+jwt" with iss/aud/exp and
 *   authorization_details), required by hubs running the Mercure 1.0 protocol
 *   without compatibility mode. The hub validates "aud" against its own
 *   request-derived URL (e.g. http://localhost/.well-known/mercure), so pass
 *   --audience when the hub is reached under a different origin.
 */
class MercureCreateJwtCommand
{
    private const PROTOCOL_0X = '0.x';
    private const PROTOCOL_1_0 = '1.0';
    private const DEFAULT_EXPIRY = 3600;

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Path to a file containing the JWT signing key (preferred)')] ?string $secretFile = null,
        #[Option(description: 'JWT signing key (insecure: visible in process list, use --secret-file instead)')] ?string $key = null,
        #[Option(description: '0.x only: payload in JSON (default: publish all topics)')] ?string $payload = null,
        #[Option(description: 'Mercure protocol: 0.x (legacy claim) or 1.0 (RFC 9068)')] string $protocol = self::PROTOCOL_0X,
        #[Option(description: '1.0 only: issuer (iss) claim recorded in the token')] string $issuer = 'opensalt',
        #[Option(description: '1.0 only: subject (sub) claim; defaults to the issuer')] ?string $subject = null,
        #[Option(description: '1.0 only: audience (aud) claim; defaults to the hub URL derived from the request origin')] ?string $audience = null,
        #[Option(description: '1.0 only: token lifetime in seconds')] int $expiry = self::DEFAULT_EXPIRY,
        #[Option(description: '1.0 only: comma-separated topic list (or * for all)')] string $topics = '*',
        #[Option(description: '1.0 only: comma-separated actions: publish, subscribe')] string $actions = 'publish',
    ): int {
        if (null !== $secretFile) {
            if (!is_readable($secretFile)) {
                $io->error(sprintf('Cannot read secret file: %s', $secretFile));

                return Command::FAILURE;
            }
            $key = trim(file_get_contents($secretFile));
        }

        if (null === $key || '' === $key) {
            $io->error('A signing key is required. Use --secret-file=<path> or --key=<value>.');

            return Command::FAILURE;
        }

        $token = match ($protocol) {
            self::PROTOCOL_0X => $this->createLegacyToken($io, $key, $payload),
            self::PROTOCOL_1_0 => $this->createProtocol10Token($io, $key, $issuer, $subject, $audience, $expiry, $topics, $actions),
            default => null,
        };

        if (null === $token) {
            return Command::FAILURE;
        }

        $io->writeln($token);

        return Command::SUCCESS;
    }

    private function createLegacyToken(SymfonyStyle $io, string $key, ?string $payload): string
    {
        $claims = [
            'mercure' => [
                'publish' => [
                    '*',
                ],
            ],
        ];
        if (null !== $payload) {
            $claims = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        }

        return JWT::encode($claims, $key, 'HS256');
    }

    private function createProtocol10Token(
        SymfonyStyle $io,
        string $key,
        string $issuer,
        ?string $subject,
        ?string $audience,
        int $expiry,
        string $topics,
        string $actions,
    ): ?string {
        $actionList = array_values(array_filter(array_map(trim(...), explode(',', $actions))));
        $invalid = array_diff($actionList, [Grant::ACTION_PUBLISH, Grant::ACTION_SUBSCRIBE]);
        if ([] !== $invalid) {
            $io->error(sprintf('Invalid action(s): %s. Allowed: publish, subscribe.', implode(', ', $invalid)));

            return null;
        }

        if ([] === $actionList) {
            $io->error('At least one action is required for protocol 1.0 tokens.');

            return null;
        }

        $topicList = array_values(array_filter(array_map(trim(...), explode(',', $topics))));
        if ([] === $topicList) {
            $io->error('At least one topic is required for protocol 1.0 tokens.');

            return null;
        }

        if (null === $audience || '' === $audience) {
            $io->error('An audience is required for protocol 1.0 tokens. Pass --audience with the hub URL '
                .'the request will use, e.g. http://localhost/.well-known/mercure.');

            return null;
        }

        $additionalClaims = array_filter([
            'iss' => $issuer,
            'sub' => $subject ?? $issuer,
            'client_id' => $subject ?? $issuer,
            'aud' => $audience,
        ], static fn (string $value): bool => '' !== $value);

        $factory = WebTokenFactory::fromSecret($key, 'HS256', $expiry);

        return $factory->create(
            [new Grant($actionList, $topicList)],
            $additionalClaims,
        );
    }
}
