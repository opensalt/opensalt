<?php

namespace App\Console;

use Kreait\Firebase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearFirebaseNotificationsCommand extends Command
{
    protected static $defaultName = 'firebase:clear';
    /**
     * @var Firebase|null
     */
    private $firebase;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(?Firebase $firebase, ?string $prefix = null)
    {
        parent::__construct();
        $this->firebase = $firebase;
        $this->prefix = !empty($prefix) ? $prefix : 'opensalt';
    }

    protected function configure(): void
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Clear Firebase notifications')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if (null === $this->firebase) {
            $output->writeln('<info>Firebase is not configured, nothing to do.</info>');

            return 0;
        }

        $prefix = $this->prefix;
        $path = "/{$prefix}/doc";
        $db = $this->firebase->getDatabase();
        $db->getReference($path)->remove();

        $output->writeln(sprintf('<info>Firebase prefix "%s" cleared.</info>', $prefix));

        return 0;
    }
}
