<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

trait LoggerTrait
{
    use \Psr\Log\LoggerTrait;

    private LoggerInterface $logger;

    /**
     * @required
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed  $level
     * @param string $message
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
