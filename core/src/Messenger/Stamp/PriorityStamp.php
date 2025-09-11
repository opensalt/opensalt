<?php

declare(strict_types=1);

namespace App\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

readonly class PriorityStamp implements StampInterface
{
    public const string HIGH = 'high';
    public const string MEDIUM = 'medium';
    public const string LOW = 'low';
    public const string VERY_LOW = 'very_low';

    public function __construct(private string $priority)
    {
    }

    public function getPriority(): string
    {
        return $this->priority;
    }
}
