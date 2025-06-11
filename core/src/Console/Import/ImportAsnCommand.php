<?php

declare(strict_types=1);

namespace App\Console\Import;

use App\Command\Import\ImportAsnFromUrlCommand;
use App\Console\BaseDispatchingCommand;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('import:asn', 'Import ASN Standards Document')]
class ImportAsnCommand extends BaseDispatchingCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('asnId', InputArgument::REQUIRED, 'Identifier for ASN Document')
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'Document creator')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $asnId = $input->getArgument('asnId');
        $creator = $input->getOption('creator');

        $output->writeln(sprintf('<info>Starting import of %s</info>', $asnId));

        try {
            $command = new ImportAsnFromUrlCommand($asnId, $creator);
            $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);

            $output->writeln('<info>Done.</info>');
        } catch (\Exception $exception) {
            $output->write($exception->getMessage());
            $output->writeln('<error>Error importing document from ASN.</error>');

            return Command::FAILURE; // Fail out of command
        }

        return Command::SUCCESS;
    }
}
