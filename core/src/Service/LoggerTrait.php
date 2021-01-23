<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

trait LoggerTrait
{
    use \Psr\Log\LoggerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @required
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function log($level, $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
