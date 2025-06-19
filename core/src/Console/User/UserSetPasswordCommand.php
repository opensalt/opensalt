<?php

declare(strict_types=1);

namespace App\Console\User;

use App\Command\User\SetUserPasswordCommand;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'salt:user:set-password',
    description: 'Set the password for a local user'
)]
class UserSetPasswordCommand
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Email address or username of the user to change')] ?string $username = null,
        #[Argument(description: 'New password for the user')] ?string $password = null,
    ): int {
        $helper = new QuestionHelper();
        if (empty($username)) {
            $question = new Question('Email address or username of new user: ');
            $question->setValidator(function (string $value): string {
                if ('' === trim($value)) {
                    throw new \Exception('The username can not be empty');
                }

                return $value;
            });
            $username = $helper->ask($input, $output, $question);
        }
        if (empty($password)) {
            $question = new Question('New password for the user (leave empty to generate one): ');
            $password = $helper->ask($input, $output, $question);
        }
        $username = trim($username);
        if (null !== $password) {
            $password = trim($password);
        }
        $command = new SetUserPasswordCommand($username, $password);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
        $newPassword = $command->getPlainPassword();
        if (null === $password || '' === $password) {
            $io->writeln(sprintf('The password for "%s" has been set to "%s".', $username, $newPassword));
        } else {
            $io->writeln(sprintf('The password for "%s" has been set.', $username));
        }

        return Command::SUCCESS;
    }
}
