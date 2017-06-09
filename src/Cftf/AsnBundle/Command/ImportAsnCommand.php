<?php

namespace Cftf\AsnBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportAsnCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:asn')
            ->setDescription('Import ASN Standards Document')
            ->addArgument('asnId', InputArgument::REQUIRED, 'Identifier for ASN Document')
            ->addOption('creator', null, InputOption::VALUE_OPTIONAL, 'Document creator')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $asnId = $input->getArgument('asnId');
        $creator = $input->getOption('creator');

        $asnImport = $this->getContainer()->get('cftf_import.asn');

        try {
            $asnImport->generateFrameworkFromAsn($asnId, $creator);

            $output->writeln('Done.');
        } catch (\Exception $e) {
            $output->writeln('<error>Error importing document from ASN.</error>');

            return 1; // Fail out of command
        }
    }
}
