<?php

declare(strict_types=1);

namespace App\Console\User;

use App\Command\User\RemoveUserRoleCommand;
use App\Entity\User\User;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'salt:user:remove-role',
    description: 'Remove a role from a local user'
)]
class UserRemoveRoleCommand
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Email address or username of the user to change')] ?string $username = null,
        #[Argument(description: 'Role to remove from the user (editor, admin, super-user)')] ?string $role = null,
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
        if (empty($role)) {
            $question = new ChoiceQuestion('Role to remove from the user: ', ['viewer', 'editor', 'admin', 'super user'], 0);
            $role = $helper->ask($input, $output, $question);
        }
        try {
            $role = trim($role);
            $role = 'ROLE_'.preg_replace('/[^A-Z]/', '_', strtoupper($role));
            if (!\in_array($role, User::USER_ROLES, true)) {
                throw new \RuntimeException(sprintf('Role "%s" is not valid.', $role));
            }
        } catch (\Exception $exception) {
            $io->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }
        $username = trim($username);
        $command = new RemoveUserRoleCommand($username, $role);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
        $io->writeln(sprintf('The role "%s" has been removed.', $role));

        return Command::SUCCESS;
    }
}
