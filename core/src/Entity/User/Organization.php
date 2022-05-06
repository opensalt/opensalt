<?php

namespace App\Entity\User;

use App\Entity\Framework\LsDoc;
use App\Repository\User\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: 'salt_org')]
#[UniqueEntity('name', message: 'The organization name is already being used')]
class Organization
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(groups: ['registration', 'Default'])]
    protected string $name;

    /**
     * @var Collection<array-key, User>
     */
    #[ORM\OneToMany(mappedBy: 'org', targetEntity: User::class, fetch: 'EXTRA_LAZY', indexBy: 'id')]
    private Collection $users;

    /**
     * @var Collection<array-key, LsDoc>
     */
    #[ORM\OneToMany(mappedBy: 'org', targetEntity: LsDoc::class, fetch: 'EXTRA_LAZY', indexBy: 'id')]
    protected Collection $frameworks;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->frameworks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): Organization
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<array-key, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): Organization
    {
        $this->users->add($user);

        return $this;
    }

    /**
     * Remove a user from the organization
     */
    public function removeUser(User $user): Organization
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Get the list of frameworks owned by the organization
     *
     * @return Collection<array-key, LsDoc>
     */
    public function getFrameworks(): Collection
    {
        return $this->frameworks;
    }
}
