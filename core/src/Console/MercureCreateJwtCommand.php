<?php

namespace App\Console;

use Firebase\JWT\JWT;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('mercure:create-jwt', 'Create a JWT key to use with Mercure')]
class MercureCreateJwtCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'JWT Key to use')
            ->addOption('payload', null, InputOption::VALUE_OPTIONAL, 'Optional payload in JSON')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $key = $input->getArgument('key');
        $payload = [
            'mercure' => [
                'publish' => [
                    '*',
                ],
            ],
        ];

        $passedPayload = $input->getOption('payload');
        if (null !== $passedPayload) {
            $payload = json_decode($passedPayload, true, 512, JSON_THROW_ON_ERROR);
        }

        $out = JWT::encode($payload, $key, 'HS256');

        $io->writeln($out);

        return Command::SUCCESS;
    }
}
