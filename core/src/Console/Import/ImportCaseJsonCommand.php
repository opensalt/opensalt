<?php

declare(strict_types=1);

namespace App\Console\Import;

use App\Entity\User\AccessGroup;
use App\Event\CommandEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'import:case-json',
    description: 'Import CASE JSON file'
)]
class ImportCaseJsonCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'JSON File')] string $filename,
        #[Option(description: 'Title of the framework')] string $title = 'Imported CSV',
        #[Option(description: 'Creator of the framework')] string $creator = 'System',
    ): int {
        $fileContent = file_get_contents($filename);
        if (false === $fileContent) {
            $io->writeln('File not found.');

            return Command::FAILURE;
        }
        $org = $this->em->getRepository(AccessGroup::class)->findOneByName('PCG');
        $command = new \App\Command\Import\ImportCaseJsonCommand($fileContent, $org);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
        $io->writeln('Done.');

        return Command::SUCCESS;
    }
}
