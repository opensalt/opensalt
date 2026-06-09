<?php

namespace App\Entity\User;

use App\Repository\User\ApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use YOCLIB\Multiformats\Multibase\Multibase;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(length: 255)]
    public private(set) string $name;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    public private(set) ?\DateTimeImmutable $expiresAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) User $user;

    #[ORM\Column(length: 255)]
    public private(set) string $tokenHash;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $lastUsed = null {
        set(?\DateTimeInterface $value) => \DateTimeImmutable::createFromInterface($value ?? new \DateTimeImmutable());
    }

    public private(set) ?string $token = null {
        get {
            if (null === $this->token) {
                throw new \LogicException('The token is only available once');
            }

            return 't1-' . $this->encodedId($this->token) . '-' . $this->token;
        }
    }

    private function __construct(User $user, string $name, ?\DateTimeImmutable $expiresAt)
    {
        $this->name = $name;
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;

        $token = $this->generateToken();
        $this->token = $token;
        $this->tokenHash = $this->makeTokenHash($token);
    }

    public static function createToken(User $user, string $name, ?\DateTimeImmutable $expiresAt): self
    {
        $token = new self($user, $name, $expiresAt);

        return $token;
    }

    public static function decodedId(#[\SensitiveParameter] string $tokenHeader): int
    {
        [$type, $encodedId, $token] = explode('-', $tokenHeader);

        if ('t1' !== $type) {
            throw new \InvalidArgumentException('Invalid token type');
        }

        $key = substr(Multibase::decode($token, Multibase::BASE58FLICKR), 0, 4);
        $unwrappedId = Multibase::decode($encodedId, Multibase::BASE58FLICKR);

        return unpack('N', $unwrappedId ^ $key)[1];
    }

    public function verifyToken(#[\SensitiveParameter] string $tokenHeader): bool
    {
        [$type, , $token] = explode('-', $tokenHeader);

        if ('t1' !== $type) {
            throw new \InvalidArgumentException('Invalid token');
        }

        if (null !== $this->expiresAt && $this->expiresAt < new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Invalid token');
        }

        if (self::decodedId($tokenHeader) !== $this->id) {
            throw new \InvalidArgumentException('Invalid token');
        }

        return password_verify($token, $this->tokenHash);
    }

    public function needsRehash(#[\SensitiveParameter] string $tokenHeader): bool
    {
        return password_needs_rehash($this->tokenHash, PASSWORD_ARGON2ID);
    }

    public function rehashToken(#[\SensitiveParameter] string $tokenHeader): void
    {
        if ($this->needsRehash($tokenHeader)) {
            [, , $token] = explode('-', $tokenHeader);
            $this->tokenHash = $this->makeTokenHash($token);
        }
    }

    private function generateToken(): string
    {
        return Multibase::encode(Multibase::BASE58FLICKR, random_bytes(20), false);
    }

    private function makeTokenHash(#[\SensitiveParameter] string $token): string
    {
        if (strlen($token) < 20) {
            throw new \InvalidArgumentException('The token must be at least 20 characters long');
        }

        return password_hash($token, PASSWORD_ARGON2ID);
    }

    private function encodedId(string $token): string
    {
        if (null === $this->id) {
            throw new \InvalidArgumentException('The token must be saved first');
        }

        $key = substr(Multibase::decode($token, Multibase::BASE58FLICKR), 0, 4);
        $id = pack('N', $this->id);

        return Multibase::encode(Multibase::BASE58FLICKR, $id ^ $key, false);
    }
}
