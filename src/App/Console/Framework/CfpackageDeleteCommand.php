<?php

namespace App\Console\Framework;

use App\Command\Framework\DeleteDocumentCommand;
use App\Console\BaseDoctrineCommand;
use App\Event\CommandEvent;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CfpackageDeleteCommand extends BaseDoctrineCommand
{
    protected static $defaultName = 'cfpackage:delete';

    protected function configure(): void
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Permanently delete a CFPackage')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of LSDoc for the package')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Delete without prompting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lsDocId = $input->getArgument('id');

        $lsDocRepo = $this->em->getRepository(LsDoc::class);

        $lsDoc = $lsDocRepo->find($lsDocId);
        if (!$lsDoc) {
            $output->writeln("<error>LSDoc with id '{$lsDocId}' not found.</error>");
            return;
        }

        if (!$input->getOption('yes')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("<question>Do you really want to delete '{$lsDoc->getTitle()}'? (y/n)</question> ", false);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<info>Not deleting LSDoc.</info>');

                return;
            }
        }

        $progress = new ProgressBar($output, 8);
        $progress->start();

        $callback = function($message = '') use ($progress) {
            $progress->setMessage(' '.$message);
            $progress->advance();
        };

        $command = new DeleteDocumentCommand($lsDoc, $callback);
        $this->dispatcher->dispatch(CommandEvent::class, new CommandEvent($command));

        $output->writeln('<info>Deleted.</info>');
    }

}
