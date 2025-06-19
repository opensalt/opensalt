<?php

declare(strict_types=1);

namespace App\Console\Framework;

use App\Command\Framework\DeleteDocumentCommand;
use App\Entity\Framework\LsDoc;
use App\Event\CommandEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'cfpackage:delete',
    description: 'Permanently delete a CFPackage'
)]
class CfpackageDeleteCommand
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
        #[Argument(description: 'Id of LSDoc for the package')] int $id,
        #[Option(description: 'Delete without prompting', shortcut: 'y')] bool $yes = false,
    ): int {
        $lsDocRepo = $this->em->getRepository(LsDoc::class);
        $lsDoc = $lsDocRepo->find($id);
        if (null === $lsDoc) {
            $io->writeln(sprintf("<error>LSDoc with id '%s' not found.</error>", $id));

            return Command::FAILURE;
        }
        if (!$yes) {
            $helper = new QuestionHelper();
            $question = new ConfirmationQuestion(sprintf("<question>Do you really want to delete '%s'? (y/n)</question> ", $lsDoc->getTitle()), false);
            if (!$helper->ask($input, $output, $question)) {
                $io->writeln('<info>Not deleting LSDoc.</info>');

                return Command::INVALID;
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
        $io->writeln('<info>Deleted.</info>');

        return Command::SUCCESS;
    }
}
