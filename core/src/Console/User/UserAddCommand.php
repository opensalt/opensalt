<?php

declare(strict_types=1);

namespace App\Console\User;

use App\Command\User\AddUserByNameCommand;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Event\CommandEvent;
use App\Repository\User\AccessGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'salt:user:add',
    description: 'Add a local user'
)]
class UserAddCommand
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly EntityManagerInterface $em,
        private readonly AccessGroupRepository $accessGroupRepository,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Email address or username of the new user')] ?string $username = null,
        #[Argument(description: 'Group name for the new user')] ?string $group = null,
        #[Option(description: 'Initial password for the new user', shortcut: 'p')] ?string $password = null,
        #[Option(description: 'Role to give the new user (editor, admin, super-user)', shortcut: 'r')] ?string $role = null,
    ): int {
        $helper = new QuestionHelper();
        $em = $this->em;
        if (empty($group)) {
            $groupObjs = $this->accessGroupRepository->findAll();
            $groups = [];
            foreach ($groupObjs as $o) {
                $groups[] = $o->getName();
            }
            $question = new Question('Group name for the new user: ');
            $question->setAutocompleterValues($groups);
            $question->setValidator(function (string $value) use ($em): string {
                if ('' === trim($value)) {
                    throw new \Exception('The group name must exist');
                }
                $group = $em->getRepository(AccessGroup::class)->findOneByName($value);
                if (null === $group) {
                    throw new \Exception('The group name must exist');
                }

                return $value;
            });
            $group = $helper->ask($input, $output, $question);
        }
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
            $question = new Question('Initial password for new user: ');
            $question->setValidator(function (string $value): string {
                if ('' === trim($value)) {
                    throw new \Exception('The password can not be empty');
                }

                return $value;
            });
            $password = $helper->ask($input, $output, $question);
        }
        if (empty($role)) {
            $roleList = [];
            foreach (User::getUserRoles() as $r) {
                $roleName = str_replace('ROLE_', '', $r);
                $roleList[] = strtolower(preg_replace('/[^A-Z]/', ' ', $roleName));
            }
            $question = new ChoiceQuestion('Role to give the new user: ', $roleList, 0);
            $role = $helper->ask($input, $output, $question);
        }
        $username = trim($username);
        $group = trim($group);
        $password = trim($password);
        $role = trim($role);
        if ('' === $role) {
            $role = 'user';
        }
        $role = 'ROLE_'.preg_replace('/[^A-Z]/', '_', strtoupper($role));
        if (!in_array($role, User::USER_ROLES)) {
            $io->writeln(sprintf('<error>Role "%s" is not valid.</error>', $role));

            return Command::FAILURE;
        }
        $groupObj = $em->getRepository(AccessGroup::class)->findOneByName($group);
        if (empty($groupObj)) {
            $io->writeln(sprintf('<error>Group "%s" is not valid.</error>', $group));

            return Command::FAILURE;
        }
        $command = new AddUserByNameCommand($username, $groupObj, $password, $role);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
        $newPassword = $command->getNewPassword();
        if ('' === $password) {
            $io->writeln(sprintf('The user "%s" has been added with password "%s".', $username, $newPassword));
        } else {
            $io->writeln(sprintf('The user "%s" has been added.', $username));
        }

        return Command::SUCCESS;
    }
}
