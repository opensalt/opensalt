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
        $jsonClient = $this->getContainer()->get('csa_guzzle.client.json');

        foreach ([
                     'http://asn.jesandco.org/resources/',
                     'http://asn.desire2learn.com/resources/',
                 ] as $urlPrefix) {
            $asnResponse = $jsonClient->request(
                'GET',
                $urlPrefix.$asnId.'_full.json',
                [
                    'timeout' => 60,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'http_errors' => false,
                ]
            );

            if ($asnResponse->getStatusCode() === 200) {
                break;
            }

            $output->writeln("Failed URL: {$urlPrefix}{$asnId}_full.json");
            $output->writeln('  Response: '.$asnResponse->getReasonPhrase());
        }

        if ($asnResponse->getStatusCode() !== 200) {
            $output->writeln('Error getting document from ASN.');
        }

        //$asnDoc = file_get_contents('/var/www/html/tmp/D10003FB.json');
        $asnDoc = $asnResponse->getBody()->getContents();

        $asnImport = $this->getContainer()->get('cftf_import.asn');
        $asnImport->parseAsnDocument($asnDoc, $creator);

        $output->writeln('Done.');
    }

}
