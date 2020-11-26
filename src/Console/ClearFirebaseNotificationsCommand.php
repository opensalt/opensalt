<?php

namespace App\Console;

use Kreait\Firebase\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearFirebaseNotificationsCommand extends Command
{
    protected static $defaultName = 'firebase:clear';
    private ?Database $firebaseDb;

    private string $firebasePrefix;

    public function __construct(?Database $firebaseDb, ?string $firebasePrefix = null)
    {
        parent::__construct();
        $this->firebaseDb = $firebaseDb;
        $this->firebasePrefix = !empty($firebasePrefix) ? $firebasePrefix : 'opensalt';
    }

    protected function configure(): void
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Clear Firebase notifications')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null === $this->firebaseDb) {
            $output->writeln('<info>Firebase is not configured, nothing to do.</info>');

            return 0;
        }

        $prefix = $this->firebasePrefix;
        $path = "/{$prefix}/doc";
        $db = $this->firebaseDb;
        $db->getReference($path)->remove();

        $output->writeln(sprintf('<info>Firebase prefix "%s" cleared.</info>', $prefix));

        return 0;
    }
}
