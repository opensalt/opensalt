<?php

namespace App\Console\Import;

use App\Console\BaseDoctrineCommand;
use App\Entity\User\Organization;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('import:case-json', 'Import CASE JSON file')]
class ImportCaseJsonCommand extends BaseDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'JSON File')
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'Title of the framework', 'Imported CSV')
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'Creator of the framework', 'System')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');

        $fileContent = file_get_contents($filename);

        $org = $this->em->getRepository(Organization::class)->findOneByName('PCG');

        $command = new \App\Command\Import\ImportCaseJsonCommand($fileContent, $org);
//        $command = new ImportGenericCsvCommand($filename, $input->getOption('creator'), $input->getOption('title'));
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);

        $output->writeln('Done.');

        return (int) Command::SUCCESS;
    }
}
