<?php

declare(strict_types=1);

namespace App\Console\Mirror;

use App\Service\MirrorFramework;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'jobs:mirror',
    description: 'Find and process the next scheduled mirror job'
)]
class MirrorJobCommand
{
    public function __construct(private readonly MirrorFramework $mirrorFramework)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
    ): int {
        $io->comment('Find and process next mirror job');
        $framework = $this->mirrorFramework->mirrorNext();
        if (null === $framework) {
            $io->comment('Nothing to do');

            return Command::SUCCESS;
        }
        $io->success(sprintf('Updated %s', $framework->getIdentifier()));

        return Command::SUCCESS;
    }
}
