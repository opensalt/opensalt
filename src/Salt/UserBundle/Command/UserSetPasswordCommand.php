<?php

namespace Salt\UserBundle\Command;

use App\Command\User\SetUserPasswordCommand;
use App\Event\CommandEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class UserSetPasswordCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('salt:user:set-password')
            ->setDescription('Set the password for a local user')
            ->addArgument('username', InputArgument::REQUIRED, 'Email address or username of the user to change')
            ->addArgument('password', InputArgument::OPTIONAL, 'New password for the user')
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

        if (empty($input->getArgument('password'))) {
            $question = new Question('New password for the user (leave empty to generate one): ');
            $password = $helper->ask($input, $output, $question);
            $input->setArgument('password', $password);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = trim($input->getArgument('username'));
        $password = trim($input->getArgument('password'));

        $command = new SetUserPasswordCommand($username, $password);
        $this->getContainer()->get('event_dispatcher')
            ->dispatch(CommandEvent::class, new CommandEvent($command));
        $newPassword = $command->getPlainPassword();

        if (empty($password)) {
            $output->writeln(sprintf('The password for "%s" has been set to "%s".', $input->getArgument('username'), $newPassword));
        } else {
            $output->writeln(sprintf('The password for "%s" has been set.', $input->getArgument('username')));
        }
    }

}
