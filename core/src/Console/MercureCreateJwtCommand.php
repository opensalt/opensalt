<?php

declare(strict_types=1);

namespace App\Console;

use Firebase\JWT\JWT;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mercure:create-jwt',
    description: 'Create a JWT key to use with Mercure'
)]
class MercureCreateJwtCommand
{
    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'JWT Key to use')] string $key,
        #[Option(description: 'Optional payload in JSON')] ?string $payload = null,
    ): int {
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
