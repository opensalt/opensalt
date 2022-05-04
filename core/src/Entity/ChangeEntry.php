<?php

namespace App\Entity;

use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="salt_change",
 *     indexes={
 *         @ORM\Index(name="change_time_idx", columns={"changed_at"}),
 *         @ORM\Index(name="doc_idx", columns={"doc_id", "changed_at"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ChangeEntryRepository")
 */
class ChangeEntry
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    protected ?int $user;

    /**
     * @ORM\Column(name="username", type="string", nullable=true)
     */
    protected ?string $username;

    /**
     * @ORM\Column(name="doc_id", type="integer", nullable=true)
     */
    protected ?int $doc;

    /**
     * @ORM\Column(name="changed_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     */
    protected \DateTimeInterface $changedAt;

    /**
     * @ORM\Column(name="description", type="string", length=2048)
     */
    protected string $description;

    /**
     * @ORM\Column(name="changed", type="json", nullable=true)
     */
    protected array $changed = [];

    public function __construct(?LsDoc $doc, ?User $user, string $description, array $changed = [])
    {
        $this->doc = (null !== $doc) ? $doc->getId() : null;
        $this->user = (null !== $user) ? $user->getId() : null;
        $this->username = (null !== $user) ? $user->getUserIdentifier() : null;
        $this->description = $description;
        $this->changed = $changed;
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocId(): ?int
    {
        return $this->doc;
    }

    public function getUserId(): ?int
    {
        return $this->user;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getChangedAt(): \DateTimeInterface
    {
        return $this->changedAt;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getChanged(): array
    {
        return $this->changed;
    }
}
