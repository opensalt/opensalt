<?php

namespace App\Console\Mirror;

use App\Service\MirrorServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateListCommand extends Command
{
    protected static $defaultName = 'jobs:update-next-server';

    /**
     * @var MirrorServer
     */
    private $mirrorServer;

    public function __construct(MirrorServer $mirrorServer, string $name = null)
    {
        parent::__construct($name);
        $this->mirrorServer = $mirrorServer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Find and process the next scheduled framework list update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->comment('Find server to check');

        $server = $this->mirrorServer->updateNext();

        if (null === $server) {
            $io->comment('Nothing to do');

            return 0;
        }

        $io->success(sprintf('Updated %s', $server->getUrl()));

        return 0;
    }
}
