<?php

namespace App\Console;

use Firebase\JWT\JWT;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MercureCreateJwtCommand extends Command
{
    protected static $defaultName = 'mercure:create-jwt';

    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a JWT key to use with Mercure')
            ->addArgument('key', InputArgument::REQUIRED, 'JWT Key to use')
            ->addOption('payload', null, InputOption::VALUE_OPTIONAL, 'Option payload in JSON')
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

        if (null !== $input->getOption('payload')) {
            $payload = json_decode($input->getOption('payload'), true, 512, JSON_THROW_ON_ERROR);
        }

        $out = JWT::encode($payload, $key);

        $io->writeln($out);

        return Command::SUCCESS;
    }
}
