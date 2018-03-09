<?php

namespace App\Console\Import;

use App\Command\Import\ImportGenericCsvCommand;
use App\Console\BaseDispatchingCommand;
use App\Event\CommandEvent;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportGeneric1Command extends BaseDispatchingCommand
{
    protected static $defaultName = 'import:generic-csv';

    protected function configure(): void
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Import Generic CSV file (Type, Statement, Coding, Parent)')
            ->addArgument('filename', InputArgument::REQUIRED, 'Standards CSV File')
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'Title of the framework', 'Imported CSV')
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'Creator of the framework', 'System')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');

        $command = new ImportGenericCsvCommand($filename, $input->getOption('creator'), $input->getOption('title'));
        $this->dispatcher->dispatch(CommandEvent::class, new CommandEvent($command));

        $output->writeln('Done.');
    }
}
