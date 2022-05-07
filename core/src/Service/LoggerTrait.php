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
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        $this->logger?->log($level, $message, $context);
    }
}
