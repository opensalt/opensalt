<?php

declare(strict_types=1);

namespace App\Console\Import;

use App\Command\Import\ImportGenericCsvCommand;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'import:generic-csv',
    description: 'Import Generic CSV file (Type, Statement, Coding, Parent)'
)]
class ImportGeneric1Command
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Standards CSV File')] string $filename,
        #[Option(description: 'Title of the framework')] string $title = 'Imported CSV',
        #[Option(description: 'Creator of the framework')] string $creator = 'System',
    ): int {
        $command = new ImportGenericCsvCommand($filename, $creator, $title);
        $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
        $io->writeln('Done.');

        return Command::SUCCESS;
    }
}
