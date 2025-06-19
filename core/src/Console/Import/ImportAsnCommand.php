<?php

declare(strict_types=1);

namespace App\Console\Import;

use App\Command\Import\ImportAsnFromUrlCommand;
use App\Event\CommandEvent;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'import:asn',
    description: 'Import ASN Standards Document'
)]
class ImportAsnCommand
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Identifier for ASN Document')] string $asnId,
        #[Option(description: 'Document creator')] ?string $creator = null,
    ): int {
        $io->writeln(sprintf('<info>Starting import of %s</info>', $asnId));
        try {
            $command = new ImportAsnFromUrlCommand($asnId, $creator);
            $this->dispatcher->dispatch(new CommandEvent($command), CommandEvent::class);
            $io->writeln('<info>Done.</info>');
        } catch (\Exception $exception) {
            $io->write($exception->getMessage());
            $io->writeln('<error>Error importing document from ASN.</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
