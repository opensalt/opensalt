<?php

namespace App\Console\Import;

use App\Command\Import\ImportAsnFromUrlCommand;
use App\Event\CommandEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportAsnCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('import:asn')
            ->setDescription('Import ASN Standards Document')
            ->addArgument('asnId', InputArgument::REQUIRED, 'Identifier for ASN Document')
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'Document creator')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $asnId = $input->getArgument('asnId');
        $creator = $input->getOption('creator');

        $output->writeln("<info>Starting import of {$asnId}</info>");

        try {
            $command = new ImportAsnFromUrlCommand($asnId, $creator);
            $this->getContainer()->get('event_dispatcher')
                ->dispatch(CommandEvent::class, new CommandEvent($command));

            $output->writeln('<info>Done.</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error importing document from ASN.</error>');

            return 1; // Fail out of command
        }

        return 0;
    }
}
