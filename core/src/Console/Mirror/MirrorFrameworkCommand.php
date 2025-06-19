<?php

declare(strict_types=1);

namespace App\Console\Mirror;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportCaseJsonCommand;
use App\Service\MirrorFramework;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mirror:framework',
    description: 'Mirror a framework'
)]
class MirrorFrameworkCommand
{
    use CommandDispatcherTrait;

    public function __construct(private readonly MirrorFramework $mirrorFramework)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'URL of framework to mirror')] string $url,
    ): int {
        $io->comment(sprintf('Mirroring %s', $url));
        $framework = $this->mirrorFramework->fetchFramework($url);
        $jsonDoc = json5_decode($framework, true);
        if (!isset($jsonDoc['CFDocument']) && isset($jsonDoc['CFPackageURI'])) {
            $url = $jsonDoc['CFPackageURI']['uri'];
            $framework = $this->mirrorFramework->fetchFramework($url);
        }
        $command = new ImportCaseJsonCommand($framework);
        $this->sendCommand($command);
        $io->success('Complete');

        return Command::SUCCESS;
    }
}
