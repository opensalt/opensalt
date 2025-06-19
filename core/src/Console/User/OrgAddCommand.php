<?php

declare(strict_types=1);

namespace App\Console\User;

use App\Command\User\AddOrganizationByNameCommand;
use App\Entity\User\Organization;
use App\Event\CommandEvent;
use Doctrine\ORM\EntityManagerInterface;
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
    name: 'salt:org:add',
    description: 'Add an organization'
)]
class OrgAddCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Organization name for the new user')] ?string $org = null,
    ): int {
        $helper = new QuestionHelper();
        $em = $this->em;
        if (empty($org)) {
            $question = new Question('New organization name: ');
            $question->setValidator(function (string $value) use ($em): string {
                if ('' === trim($value)) {
                    throw new \Exception('The organization name must not be empty');
                }
                $org = $em->getRepository(Organization::class)->findOneByName($value);
                if (null !== $org) {
                    throw new \Exception('The organization name must not already exist');
                }

                return $value;
            });
            $org = $helper->ask($input, $output, $question);
        }
        $org = trim($org);
        $orgRepository = $em->getRepository(Organization::class);
        $orgObj = $orgRepository->findOneByName($org);
        if (null !== $orgObj) {
            $io->writeln(sprintf('<error>Organization "%s" already exists.</error>', $org));

            return Command::FAILURE;
        }
        $command = new AddOrganizationByNameCommand($org);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
        $io->writeln(sprintf('The organization "%s" has been added.', $org));

        return Command::SUCCESS;
    }
}
