<?php

namespace App\Service;

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

    public function log(mixed $level, \Stringable|string $message, array $context = []): void
    {
        $this->logger?->log($level, $message, $context);
    }
}
