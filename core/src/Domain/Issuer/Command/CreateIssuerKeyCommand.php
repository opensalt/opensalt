<?php

declare(strict_types=1);

namespace App\Domain\Issuer\Command;

use Jose\Component\KeyManagement\JWKFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'issuer-registry:generate-key', description: 'Generate a signing key for use by the issuer registry')]
class CreateIssuerKeyCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = JWKFactory::createECKey('P-256', ['kid' => 'placeholder', 'alg' => 'ES256']);
        $key = JWKFactory::createFromValues([...$key->jsonSerialize(), 'kid' => $key->thumbprint('sha256')]);

        $output->writeln(json_encode($key->jsonSerialize()) ?: '');

        return Command::SUCCESS;
    }
}
