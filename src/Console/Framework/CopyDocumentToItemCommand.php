<?php

namespace App\Console\Framework;

use App\Command\Framework\CopyDocumentToItemCommand as CopyDocumentToItemEventCommand;
use App\Console\BaseDoctrineCommand;
use App\Event\CommandEvent;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CopyDocumentToItemCommand extends BaseDoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('cfpackage:duplicate')
            ->setDescription('Copy a package to an item in a framework')
            ->addArgument('from', InputArgument::REQUIRED, 'Id of package to duplicate')
            ->addArgument('to', InputArgument::REQUIRED, 'Id of package to copy into')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oldDocId = $input->getArgument('from');
        $newDocId = $input->getArgument('to');

        $lsDocRepo = $this->em->getRepository(LsDoc::class);

        $oldDoc = $lsDocRepo->find($oldDocId);
        if (!$oldDoc) {
            $output->writeln("<error>Doc with id '{$oldDocId}' not found.</error>");

            return;
        }

        $newDoc = $lsDocRepo->find($newDocId);
        if (!$newDoc) {
            $output->writeln("<error>Doc with id '{$newDocId}' not found.</error>");

            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("<question>Do you really want to duplicate '{$oldDoc->getTitle()}'? (y/n)</question> ", false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not duplicating document.</info>');

            return;
        }

        $progress = new ProgressBar($output);
        $progress->start();

        $callback = function ($message = '') use ($progress) {
            $progress->setMessage(' '.$message);
            $progress->advance();
        };

        $command = new CopyDocumentToItemEventCommand($oldDoc, $newDoc, $callback);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);

        $output->writeln('<info>Duplicated.</info>');
    }
}
