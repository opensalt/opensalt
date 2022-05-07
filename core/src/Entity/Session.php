<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'auth_session')]
#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Column(name: 'id', type: 'binary', length: 128)]
    #[ORM\Id]
    private mixed $id;

    #[ORM\Column(name: 'sess_data', type: 'blob')]
    private mixed $data;

    #[ORM\Column(name: 'sess_time', type: 'integer')]
    private int $lastUsed;

    #[ORM\Column(name: 'sess_lifetime', type: 'integer')]
    private int $lifetime;

    public function getId(): string
    {
        /** @phpstan-ignore-next-line */
        if (is_resource($this->id)) {
            $this->id = stream_get_contents($this->id);
        }

        return $this->id;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getLastUsed(): int
    {
        return $this->lastUsed;
    }

    public function getLastUsedTime(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U', (string) $this->getLastUsed());
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }
}
