<?php

namespace App\Entity;

use CftfBundle\Entity\LsDoc;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Salt\UserBundle\Entity\User;

/**
 * @ORM\Table(name="salt_change",
 *     indexes={@ORM\Index(name="change_time_idx", columns={"changed_at"})}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ChangeEntryRepository")
 */
class ChangeEntry
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Salt\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    protected $user;

    /**
     * @var LsDoc
     *
     * @ORM\ManyToOne(targetEntity="CftfBundle\Entity\LsDoc")
     * @ORM\JoinColumn(name="doc_id", referencedColumnName="id", nullable=true, unique=true)
     */
    protected $doc;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="changed_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     */
    protected $changedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=2048)
     */
    protected $description;

    /**
     * @var array
     *
     * @ORM\Column(name="changed", type="json", nullable=true)
     */
    protected $changed = [];

    public function __construct(?LsDoc $doc, ?User $user, string $description, array $changed = [])
    {
        $this->doc = $doc;
        $this->user = $user;
        $this->description = $description;
        $this->changed = $changed;
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDoc(): ?LsDoc
    {
        return $this->doc;
    }

    public function getUser(): ?User
    {
        return $this->user;
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

    public function updateTo(ChangeEntry $changeEntry)
    {
        $this->doc = $changeEntry->getDoc();
        $this->user = $changeEntry->getUser();
        $this->description = $changeEntry->getDescription();
        $this->changed = $changeEntry->getChanged();
        $this->changedAt = $changeEntry->getChangedAt();
    }
}
