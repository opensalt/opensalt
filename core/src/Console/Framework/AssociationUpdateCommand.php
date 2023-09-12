<?php

namespace App\Console\Framework;

use App\Service\SubtypeUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand('association:update', 'Update associations from spreadsheets')]
class AssociationUpdateCommand extends Command
{
    public function __construct(private readonly SubtypeUpdater $updater)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'Path to spreadsheets')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(180); // increase time limit for large files

        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');

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

        return (int) Command::SUCCESS;
    }
}
