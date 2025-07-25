<?php

declare(strict_types=1);

namespace App\Console\User;

use App\Command\User\AddAccessGroupByNameCommand;
use App\Entity\User\AccessGroup;
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
    name: 'salt:group:add',
    description: 'Add an access group'
)]
class GroupAddCommand
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
        #[Argument(description: 'Group name for the new user')] ?string $group = null,
    ): int {
        $helper = new QuestionHelper();
        $em = $this->em;
        if (empty($group)) {
            $question = new Question('New group name: ');
            $question->setValidator(function (string $value) use ($em): string {
                if ('' === trim($value)) {
                    throw new \Exception('The group name must not be empty');
                }
                $group = $em->getRepository(AccessGroup::class)->findOneByName($value);
                if (null !== $group) {
                    throw new \Exception('The group name must not already exist');
                }

                return $value;
            });
            $group = $helper->ask($input, $output, $question);
        }
        $group = trim($group);
        $accessGroupRepository = $em->getRepository(AccessGroup::class);
        $groupObj = $accessGroupRepository->findOneByName($group);
        if (null !== $groupObj) {
            $io->writeln(sprintf('<error>Group "%s" already exists.</error>', $group));

            return Command::FAILURE;
        }
        $command = new AddAccessGroupByNameCommand($group);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
        $io->writeln(sprintf('The group "%s" has been added.', $group));

        return Command::SUCCESS;
    }
}
