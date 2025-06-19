<?php

declare(strict_types=1);

namespace App\Console\Framework;

use App\Command\Framework\CopyDocumentToItemCommand as CopyDocumentToItemEventCommand;
use App\Entity\Framework\LsDoc;
use App\Event\CommandEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'cfpackage:duplicate',
    description: 'Copy a package to an item in a framework'
)]
class CopyDocumentToItemCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Id of package to duplicate')] int $from,
        #[Argument(description: 'Id of package to copy into')] int $to,
    ): int {
        $lsDocRepo = $this->em->getRepository(LsDoc::class);
        $oldDoc = $lsDocRepo->find($from);
        if (null === $oldDoc) {
            $io->writeln(sprintf("<error>Doc with id '%s' not found.</error>", $from));

            return Command::FAILURE;
        }
        $newDoc = $lsDocRepo->find($to);
        if (null === $newDoc) {
            $io->writeln(sprintf("<error>Doc with id '%s' not found.</error>", $to));

            return Command::INVALID;
        }
        $helper = new QuestionHelper();
        $question = new ConfirmationQuestion(sprintf("<question>Do you really want to duplicate '%s'? (y/n)</question> ", $oldDoc->getTitle()), false);
        if (!$helper->ask($input, $output, $question)) {
            $io->writeln('<info>Not duplicating document.</info>');

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
        $io->writeln('<info>Duplicated.</info>');

        return Command::SUCCESS;
    }
}
