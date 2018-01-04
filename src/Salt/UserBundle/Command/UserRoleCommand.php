<?php

namespace Salt\UserBundle\Command;

use App\Event\CommandEvent;
use Salt\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class UserRoleCommand extends ContainerAwareCommand
{
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $helper = $this->getHelper('question');

        if (empty($input->getArgument('username'))) {
            $question = new Question('Email address or username of new user: ');
            $question->setValidator(function ($value) {
                if (trim($value) === '') {
                    throw new \Exception('The username can not be empty');
                }

                return $value;
            });
            $username = $helper->ask($input, $output, $question);
            $input->setArgument('username', $username);
        }
    }

    protected function doChange(InputInterface $input, OutputInterface $output, $commandClass): int
    {
        try {
            $role = $this->getValidRole($input->getArgument('role'));
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return 1;
        }

        $username = trim($input->getArgument('username'));

        $command = new $commandClass($username, $role);
        $this->getContainer()->get('event_dispatcher')
            ->dispatch(CommandEvent::class, new CommandEvent($command));

        return 0;
    }

    protected function getValidRole(string $inRole): string
    {
        $role = trim($inRole);
        $role = 'ROLE_'.preg_replace('/[^A-Z]/', '_', strtoupper($role));

        if (!in_array($role, User::USER_ROLES, true)) {
            throw new \RuntimeException(sprintf('Role "%s" is not valid.', $inRole));
        }

        return $role;
    }
}
