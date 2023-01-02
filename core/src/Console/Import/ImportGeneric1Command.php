<?php

namespace App\Console\Import;

use App\Command\Import\ImportGenericCsvCommand;
use App\Console\BaseDispatchingCommand;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('import:generic-csv', 'Import Generic CSV file (Type, Statement, Coding, Parent)')]
class ImportGeneric1Command extends BaseDispatchingCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'Standards CSV File')
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'Title of the framework', 'Imported CSV')
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'Creator of the framework', 'System')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');

        $command = new ImportGenericCsvCommand($filename, $input->getOption('creator'), $input->getOption('title'));
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);

        $output->writeln('Done.');

        return (int) Command::SUCCESS;
    }
}
