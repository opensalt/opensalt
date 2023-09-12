<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;

class AddExternalDocCommand extends BaseCommand
{
    #[Assert\Type(LsDoc::class)]
    #[Assert\NotNull]
    private LsDoc $doc;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Choice(['true', 'false'])]
    private string $autoload;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    private string $url;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    private string $title;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    private string $identifier;

    public function __construct(LsDoc $doc, string $identifier, string $autoload, string $url, string $title)
    {
        $this->doc = $doc;
        $this->autoload = $autoload;
        $this->url = $url;
        $this->title = $title;
        $this->identifier = $identifier;
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
