<?php

namespace App\Console\Framework;

use App\Command\Framework\DeleteDocumentCommand;
use App\Console\BaseDoctrineCommand;
use App\Entity\Framework\LsDoc;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand('cfpackage:delete', 'Permanently delete a CFPackage')]
class CfpackageDeleteCommand extends BaseDoctrineCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Id of LSDoc for the package')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Delete without prompting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lsDocId = $input->getArgument('id');

        $lsDocRepo = $this->em->getRepository(LsDoc::class);

        $lsDoc = $lsDocRepo->find((int) $lsDocId);
        if (!$lsDoc) {
            $output->writeln("<error>LSDoc with id '{$lsDocId}' not found.</error>");

            return (int) Command::FAILURE;
        }

        if (!$input->getOption('yes')) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("<question>Do you really want to delete '{$lsDoc->getTitle()}'? (y/n)</question> ", false);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<info>Not deleting LSDoc.</info>');

                return (int) Command::INVALID;
            }
        }

        $progress = new ProgressBar($output, 8);
        $progress->start();

        $callback = static function (string $message = '') use ($progress): void {
            $progress->setMessage(' '.$message);
            $progress->advance();
        };

        $command = new DeleteDocumentCommand($lsDoc, $callback);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);

        $output->writeln('<info>Deleted.</info>');

        return (int) Command::SUCCESS;
    }
}
