<?php

namespace Salt\UserBundle\Command;

use Salt\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class UserAddCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('salt:user:add')
            ->setDescription('Add a local user')
            ->addArgument('username', InputArgument::REQUIRED, 'Email address or username of the new user')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Initial password for the new user')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Role to give the new user (editor, admin, super-user)')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output) {
        parent::interact($input, $output);

        $helper = $this->getHelper('question');

        if (empty($input->getArgument('username'))) {
            $question = new Question('Email address or username of new user: ');
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('The username can not be empty');
                }

                return $value;
            });
            $username = $helper->ask($input, $output, $question);
            $input->setArgument('username', $username);
        }

        if (empty($input->getOption('password'))) {
            $question = new Question('Initial password for new user: ');
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('The password can not be empty');
                }

                return $value;
            });
            $password = $helper->ask($input, $output, $question);
            $input->setOption('password', $password);
        }

        if (empty($input->getOption('role'))) {
            $question = new ChoiceQuestion('Role to give the new user: ', ['user', 'editor', 'admin', 'site admin', 'super user'], 0);
            $role = $helper->ask($input, $output, $question);
            $input->setOption('role', $role);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$service = $this->getContainer()->get('salt.user');
        $username = trim($input->getArgument('username'));
        $password = trim($input->getOption('password'));
        $role = trim($input->getOption('role'));
        $role = 'ROLE_'.preg_replace('/[^A-Z]/', '_', strtoupper($role));

        if (!in_array($role, User::USER_ROLES)) {
            $output->writeln(sprintf('<error>Role "%s" is not valid.</error>', $input->getOption('role')));
            return;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $em->getRepository('SaltUserBundle:User');
        $userRepository->addNewUser($username, $password, $role);

        $output->writeln(sprintf('The user "%s" has been added.', $username));
    }

}
