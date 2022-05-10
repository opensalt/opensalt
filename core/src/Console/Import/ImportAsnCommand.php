<?php

namespace App\Console\Import;

use App\Command\Import\ImportAsnFromUrlCommand;
use App\Console\BaseDispatchingCommand;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('import:asn', 'Import ASN Standards Document')]
class ImportAsnCommand extends BaseDispatchingCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('asnId', InputArgument::REQUIRED, 'Identifier for ASN Document')
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'Document creator')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $asnId = $input->getArgument('asnId');
        $creator = $input->getOption('creator');

        $output->writeln("<info>Starting import of {$asnId}</info>");

        try {
            $command = new ImportAsnFromUrlCommand($asnId, $creator);
            $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);

            $output->writeln('<info>Done.</info>');
        } catch (\Exception $e) {
            $output->write($e->getMessage());
            $output->writeln('<error>Error importing document from ASN.</error>');

            return 1; // Fail out of command
        }

        return 0;
    }
}
