<?php

namespace CftfBundle\Command;

use App\Command\Framework\CopyDocumentToItemCommand as CopyDocumentToItemEventCommand;
use App\Event\CommandEvent;
use CftfBundle\Entity\LsDoc;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CopyDocumentToItemCommand extends ContainerAwareCommand
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

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $lsDocRepo = $em->getRepository(LsDoc::class);

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
        $this->getContainer()->get('event_dispatcher')
            ->dispatch(CommandEvent::class, new CommandEvent($command));

        $output->writeln('<info>Duplicated.</info>');
    }
}
