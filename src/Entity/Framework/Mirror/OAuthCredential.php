<?php

namespace App\Entity\Framework\Mirror;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="mirror_oauth")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\Mirror\OAuthCredentialRepository")
 */
class OAuthCredential
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="endpoint", type="string", nullable=false)
     */
    private $authenticationEndpoint;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_key", type="string", nullable=false)
     */
    private $key;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_secret", type="string", nullable=false)
     */
    private $secret;

    /**
     * @var array|string[]
     */
    private $scopes = [
        'http://purl.imsglobal.org/casenetwork/case/v1p0/scope/core.readonly',
    ];

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="updated_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

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
     * @return array|string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
