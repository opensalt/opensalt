<?php

declare(strict_types=1);

namespace App\Console\Framework;

use App\Service\SubtypeUpdater;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(
    name: 'association:update',
    description: 'Update associations from spreadsheets'
)]
class AssociationUpdateCommand
{
    public function __construct(private readonly SubtypeUpdater $updater)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Path to spreadsheets')] string $path,
    ): int {
        set_time_limit(180); // increase time limit for large files
        $finder = Finder::create()->files()->name('*.xlsx')->in($path);
        if ($finder->hasResults()) {
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $lineOutput = $this->updater->loadSpreadsheet($file->getRealPath());
                array_unshift($lineOutput, ['row' => '', 'msg' => 'Processing '.$file->getRealPath()]);
                $io->table(['line', 'message'], $lineOutput);
            }
        }
        $io->success('Spreadsheets loaded.');

        return Command::SUCCESS;
    }
}
