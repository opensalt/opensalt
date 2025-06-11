<?php

declare(strict_types=1);

namespace App\Console\Framework;

use App\Command\Framework\CopyDocumentToItemCommand as CopyDocumentToItemEventCommand;
use App\Console\BaseDoctrineCommand;
use App\Entity\Framework\LsDoc;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand('cfpackage:duplicate', 'Copy a package to an item in a framework')]
class CopyDocumentToItemCommand extends BaseDoctrineCommand
{
    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('from', InputArgument::REQUIRED, 'Id of package to duplicate')
            ->addArgument('to', InputArgument::REQUIRED, 'Id of package to copy into')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $oldDocId = $input->getArgument('from');
        $newDocId = $input->getArgument('to');

        $lsDocRepo = $this->em->getRepository(LsDoc::class);

        $oldDoc = $lsDocRepo->find((int) $oldDocId);
        if (null === $oldDoc) {
            $output->writeln(sprintf("<error>Doc with id '%s' not found.</error>", $oldDocId));

            return Command::FAILURE;
        }

        $newDoc = $lsDocRepo->find((int) $newDocId);
        if (null === $newDoc) {
            $output->writeln(sprintf("<error>Doc with id '%s' not found.</error>", $newDocId));

            return Command::INVALID;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(sprintf("<question>Do you really want to duplicate '%s'? (y/n)</question> ", $oldDoc->getTitle()), false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not duplicating document.</info>');

            return 3;
        }

        $progress = new ProgressBar($output);
        $progress->start();

        $callback = function (string $message = '') use ($progress): void {
            $progress->setMessage(' '.$message);
            $progress->advance();
        };

        $command = new CopyDocumentToItemEventCommand($oldDoc, $newDoc, $callback);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);

        $output->writeln('<info>Duplicated.</info>');

        return Command::SUCCESS;
    }
}
