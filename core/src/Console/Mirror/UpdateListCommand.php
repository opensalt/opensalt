<?php

declare(strict_types=1);

namespace App\Console\Mirror;

use App\Service\MirrorServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'jobs:update-next-server',
    description: 'Find and process the next scheduled framework list update'
)]
class UpdateListCommand
{
    public function __construct(private readonly MirrorServer $mirrorServer)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
    ): int {
        $io->comment('Find server to check');
        $server = $this->mirrorServer->updateNext();
        if (null === $server) {
            $io->comment('Nothing to do');

            return Command::SUCCESS;
        }
        $io->success(sprintf('Updated %s', $server->getUrl()));

        return Command::SUCCESS;
    }
}
