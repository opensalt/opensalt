<?php

namespace Salt\UserBundle\Command;

use App\Command\User\AddOrganizationByNameCommand;
use App\Event\CommandEvent;
use Salt\UserBundle\Entity\Organization;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class OrgAddCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('salt:org:add')
            ->setDescription('Add an organization')
            ->addArgument('org', InputArgument::REQUIRED, 'Organization name for the new user')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $helper = $this->getHelper('question');

        if (empty($input->getArgument('org'))) {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $question = new Question('New organization name: ');
            $question->setValidator(function ($value) use ($em) {
                if (trim($value) === '') {
                    throw new \Exception('The organization name must note be empty');
                }

                $org = $em->getRepository(Organization::class)->findOneByName($value);
                if (!empty($org)) {
                    throw new \Exception('The organization name must not already exist');
                }

                return $value;
            });
            $org = $helper->ask($input, $output, $question);
            $input->setArgument('org', $org);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = trim($input->getArgument('org'));

        $em = $this->getContainer()->get('doctrine')->getManager();
        $orgRepository = $em->getRepository(Organization::class);

        $orgObj = $orgRepository->findOneByName($org);
        if (!empty($orgObj)) {
            $output->writeln(sprintf('<error>Organization "%s" aleady exists.</error>', $org));

            return 1;
        }

        $command = new AddOrganizationByNameCommand($org);
        $this->getContainer()->get('event_dispatcher')
            ->dispatch(CommandEvent::class, new CommandEvent($command));

        $output->writeln('The organization "%s" has been added.');
    }
}
