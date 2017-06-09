<?php

namespace CftfBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CfpackageDeleteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cfpackage:delete')
            ->setDescription('Permanently delete a CFPackage')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of LSDoc for the package')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lsDocId = $input->getArgument('id');

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $lsDocRepo = $em->getRepository('CftfBundle:LsDoc');

        $lsDoc = $lsDocRepo->find($lsDocId);
        if (!$lsDoc) {
            $output->writeln("<error>LSDoc with id '{$lsDocId}' not found.</error>");
            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("<question>Do you really want to delete '{$lsDoc->getTitle()}'? (y/n)</question> ", false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not deleting LSDoc.</info>');
            return;
        }

        $progress = new ProgressBar($output, 7);
        $progress->start();

        $callback = function($message = '') use ($progress) {
            $progress->setMessage(' '.$message);
            $progress->advance();
        };

        $lsDocRepo->deleteDocument($lsDoc, $callback);

        $output->writeln('<info>Deleted.</info>');
    }

}
