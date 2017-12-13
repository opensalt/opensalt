<?php

namespace Salt\UserBundle\Command;

use App\Command\User\AddUserRoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class UserAddRoleCommand extends UserRoleCommand
{
    protected function configure()
    {
        $this
            ->setName('salt:user:add-role')
            ->setDescription('Add a role to a local user')
            ->addArgument('username', InputArgument::REQUIRED, 'Email address or username of the user to change')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to give the user (editor, admin, super-user)')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $helper = $this->getHelper('question');

        if (empty($input->getArgument('role'))) {
            $question = new ChoiceQuestion('Role to give the user: ', ['viewer', 'editor', 'admin', 'super user'], 0);
            $role = $helper->ask($input, $output, $question);
            $input->setArgument('role', $role);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (0 !== $this->doChange($input, $output, AddUserRoleCommand::class)) {
            return 1;
        }

        $output->writeln(sprintf('The role "%s" has been added.', $input->getArgument('role')));

        return 0;
    }

}
