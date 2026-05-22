<?php

declare(strict_types=1);

namespace App\VectorSearch\Message;

/**
 * Message for generating embeddings asynchronously.
 */
readonly class GenerateEmbeddingMessage
{
    public function __construct(
        private int $lsItemId,
        private ?string $text = null,
        private bool $force = false,
    ) {
    }

    public function getLsItemId(): int
    {
        return $this->lsItemId;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}
