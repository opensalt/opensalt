<?php

namespace Salt\UserBundle\Command;

use App\Command\User\AddUserByNameCommand;
use App\Event\CommandEvent;
use Salt\UserBundle\Entity\Organization;
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
            ->addArgument('org', InputArgument::REQUIRED, 'Organization name for the new user')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Initial password for the new user')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Role to give the new user (editor, admin, super-user)')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $helper = $this->getHelper('question');

        $em = $this->getContainer()->get('doctrine')->getManager();
        if (empty($input->getArgument('org'))) {
            $orgObjs = $em->getRepository(Organization::class)->findAll();
            $orgs = [];
            foreach ($orgObjs as $org) {
                $orgs[] = $org->getName();
            }

            $question = new Question('Organization name for the new user: ');
            $question->setAutocompleterValues($orgs);
            $question->setValidator(function ($value) use ($em) {
                if (trim($value) === '') {
                    throw new \Exception('The organization name must exist');
                }

                $org = $em->getRepository(Organization::class)->findOneByName($value);
                if (empty($org)) {
                    throw new \Exception('The organization name must exist');
                }

                return $value;
            });
            $org = $helper->ask($input, $output, $question);
            $input->setArgument('org', $org);
        }

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

        if (empty($input->getOption('password'))) {
            $question = new Question('Initial password for new user: ');
            $question->setValidator(function ($value) {
                if (trim($value) === '') {
                    throw new \Exception('The password can not be empty');
                }

                return $value;
            });
            $password = $helper->ask($input, $output, $question);
            $input->setOption('password', $password);
        }

        if (empty($input->getOption('role'))) {
            $roleList = [];
            foreach (User::getUserRoles() as $role) {
                $roleList[] = strtolower(preg_replace('/[^A-Z]/', ' ', str_replace('ROLE_', '', $role)));
            }
            $question = new ChoiceQuestion('Role to give the new user: ', $roleList, 0);
            $role = $helper->ask($input, $output, $question);
            $input->setOption('role', $role);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = trim($input->getArgument('username'));
        $org = trim($input->getArgument('org'));
        $password = trim($input->getOption('password'));
        $role = trim($input->getOption('role'));
        if (empty($role)) {
            $role = 'user';
        }
        $role = 'ROLE_'.preg_replace('/[^A-Z]/', '_', strtoupper($role));

        if (!in_array($role, User::USER_ROLES)) {
            $output->writeln(sprintf('<error>Role "%s" is not valid.</error>', $input->getOption('role')));

            return 1;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $orgObj = $em->getRepository(Organization::class)->findOneByName($org);
        if (empty($orgObj)) {
            $output->writeln(sprintf('<error>Organization "%s" is not valid.</error>', $org));

            return 1;
        }

        $command = new AddUserByNameCommand($username, $orgObj, $password, $role);
        $this->getContainer()->get('event_dispatcher')
            ->dispatch(CommandEvent::class, new CommandEvent($command));
        $newPassword = $command->getNewPassword();

        if (empty($password)) {
            $output->writeln(sprintf('The user "%s" has been added with password "%s".', $username, $newPassword));
        } else {
            $output->writeln(sprintf('The user "%s" has been added.', $username));
        }
    }

}
