<?php

declare(strict_types=1);

namespace App\Console;

use Firebase\JWT\JWT;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mercure:create-jwt',
    description: 'Create a JWT key to use with Mercure'
)]
/**
 * Creates a JWT for Mercure hub authentication.
 *
 * Security notes:
 * - Prefer --secret-file over --key to avoid leaking the key via /proc/<pid>/cmdline.
 * - The key material remains in process memory for the lifetime of the command.
 *   PHP does not offer secure memory zeroing, so this is an inherent limitation.
 */
class MercureCreateJwtCommand
{
    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Path to a file containing the JWT signing key (preferred)')] ?string $secretFile = null,
        #[Option(description: 'JWT signing key (insecure: visible in process list, use --secret-file instead)')] ?string $key = null,
        #[Option(description: 'Optional payload in JSON')] ?string $payload = null,
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

        $defaultPayload = [
            'mercure' => [
                'publish' => [
                    '*',
                ],
            ],
        ];
        if (null !== $payload) {
            $defaultPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        }
        $out = JWT::encode($defaultPayload, $key, 'HS256');
        $io->writeln($out);

        return Command::SUCCESS;
    }
}
