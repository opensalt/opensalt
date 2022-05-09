<?php

namespace App\Service;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait LoggerTrait
{
    use \Psr\Log\LoggerTrait;

    private ?LoggerInterface $logger = null;

    #[Required]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed  $level
     * @param string $message
     *
     * @throws InvalidArgumentException
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logger?->log($level, $message, $context);
    }
}
