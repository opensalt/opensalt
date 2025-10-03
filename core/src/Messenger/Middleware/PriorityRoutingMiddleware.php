<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use App\Messenger\Stamp\PriorityStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

class PriorityRoutingMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Check if the message has a PriorityStamp
        $priorityStamp = $envelope->last(PriorityStamp::class);

        if ($priorityStamp instanceof PriorityStamp) {
            $priority = $priorityStamp->getPriority();

            // Determine the transport based on priority
            $transport = match ($priority) {
                PriorityStamp::HIGH => 'async_priority_high',
                PriorityStamp::MEDIUM => 'async_priority_medium',
                PriorityStamp::LOW => 'async_priority_low',
                PriorityStamp::VERY_LOW => 'async_priority_very_low',
                default => throw new \RuntimeException('Unknow priority level'),
            };

            // Add a TransportNamesStamp to redirect the message
            $envelope = $envelope->with(new TransportNamesStamp([$transport]));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
