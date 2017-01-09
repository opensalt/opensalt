<?php

namespace Salt\UserBundle\Command;

use Salt\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class UserRemoveRoleCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('salt:user:remove-role')
            ->setDescription('Remove a role from a local user')
            ->addArgument('username', InputArgument::REQUIRED, 'Email address or username of the user to change')
            ->addArgument('role', InputArgument::REQUIRED, 'Role to remove from the user (editor, admin, super-user)')
        ;
    }

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

        if (empty($input->getArgument('role'))) {
            $question = new ChoiceQuestion('Role to give the new user: ', ['viewer', 'editor', 'admin', 'super user'], 0);
            $role = $helper->ask($input, $output, $question);
            $input->setArgument('role', $role);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = trim($input->getArgument('username'));
        $role = trim($input->getArgument('role'));
        $role = 'ROLE_'.preg_replace('/[^A-Z]/', '_', strtoupper($role));

        if (!in_array($role, User::USER_ROLES)) {
            $output->writeln(sprintf('<error>Role "%s" is not valid.</error>', $input->getArgument('role')));

            return;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $em->getRepository('SaltUserBundle:User');
        $userRepository->removeRoleFromUser($username, $role);

        $output->writeln(sprintf('The role "%s" has been removed.', $input->getArgument('role')));
    }

}
