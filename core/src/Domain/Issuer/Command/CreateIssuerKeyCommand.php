<?php

declare(strict_types=1);

namespace App\Domain\Issuer\Command;

use Jose\Component\KeyManagement\JWKFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'issuer-registry:generate-key', description: 'Generate a signing key for use by the issuer registry')]
class CreateIssuerKeyCommand
{
    public function __invoke(SymfonyStyle $io): int
    {
        $key = JWKFactory::createECKey('P-256', ['kid' => 'placeholder', 'alg' => 'ES256']);
        $key = JWKFactory::createFromValues([...$key->jsonSerialize(), 'kid' => $key->thumbprint('sha256')]);

        $io->writeln(json_encode($key->jsonSerialize()) ?: '');

        return Command::SUCCESS;
    }
}
