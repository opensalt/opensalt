<?php

namespace App\Entity\Framework\Mirror;

use App\Repository\Framework\Mirror\OAuthCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'mirror_oauth')]
#[ORM\Entity(repositoryClass: OAuthCredentialRepository::class)]
class OAuthCredential
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'endpoint', type: 'string', nullable: false)]
    private string $authenticationEndpoint;

    #[ORM\Column(name: 'auth_key', type: 'string', nullable: false)]
    private string $key;

    #[ORM\Column(name: 'auth_secret', type: 'string', nullable: false)]
    private string $secret;

    /**
     * @var array<array-key, string>
     */
    private array $scopes = [
        'http://purl.imsglobal.org/casenetwork/case/v1p0/scope/core.readonly',
    ];

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime', precision: 6)]
    private \DateTimeInterface $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthenticationEndpoint(): string
    {
        return $this->authenticationEndpoint;
    }

    public function setAuthenticationEndpoint(string $authenticationEndpoint): self
    {
        $this->authenticationEndpoint = $authenticationEndpoint;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return array<array-key, string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
