<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class AddExternalDocCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $identifier,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['true', 'false'])]
        private readonly string $autoload,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $url,
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $title,
    ) {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getAutoload(): string
    {
        return $this->autoload;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
