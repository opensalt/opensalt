<?php

namespace Salt\SiteBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Salt\UserBundle\Entity\User;

/**
 * Comment
 *
 * @ORM\Entity(repositoryClass="CommentRepository")
 * @ORM\Table(name="salt_comment")
 *
 * @Serializer\VirtualProperty(
 *     "fullname",
 *     exp="object.getFullname()",
 *     options={@Serializer\SerializedName("fullname")}
 *  )
 */
class Comment
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Comment
     *
     * @ORM\ManyToOne(targetEntity="\Salt\SiteBundle\Entity\Comment")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     *
     * @Serializer\Accessor(getter="getParentId")
     * @Serializer\ReadOnly
     * @Serializer\Type("int")
     */
    private $parent;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="\Salt\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Serializer\Exclude()
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $item;

    /**
     * @var CommentUpvote[]|Collection
     *
     * @ORM\OneToMany(targetEntity="CommentUpvote", mappedBy="comment")
     *
     * @Serializer\Accessor(getter="getUpvoteCount")
     * @Serializer\ReadOnly
     * @Serializer\Type("int")
     * @Serializer\SerializedName("upvote_count")
     */
    private $upvotes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Serializer\SerializedName("created")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Serializer\SerializedName("modified")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @var bool
     */
    private $createdByCurrentUser;

    /**
     * @var bool
     */
    private $userHasUpvoted;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->upvotes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set parent
     *
     * @param int $parent
     *
     * @return Comment
     */
    public function setParent(Comment $parent = null): Comment
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Comment|null
     */
    public function getParent(): ?Comment
    {
        return $this->parent;
    }

    /**
     * Get the id of a parent
     *
     * @return int|null
     */
    public function getParentId(): ?int
    {
        if (null !== $this->parent) {
            return $this->parent->getId();
        }

        return null;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Comment
     */
    public function setContent($content): Comment
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Comment
     */
    public function setUser(User $user): Comment
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set item
     *
     * @param string $item
     *
     * @return Comment
     */
    public function setItem(string $item): Comment
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return string
     */
    public function getItem(): string
    {
        return $this->item;
    }

    /**
     * Get fullname
     *
     * @return string
     */
    public function getFullname(): string
    {
        $displayName = preg_replace('/@.*/', '', $this->getUser()->getUsername());
        return $displayName;
    }

    /**
     * Set createdByCurrentUser
     *
     * @param bool $createdByCurrentUser
     *
     * @return Comment
     */
    public function setCreatedByCurrentUser(bool $createdByCurrentUser): Comment
    {
        $this->createdByCurrentUser = $createdByCurrentUser;

        return $this;
    }

    public function updateStatusForUser(User $user): Comment
    {
        if ($this->getUser()->getId() === $user->getId()) {
            $this->setCreatedByCurrentUser(true);
        }

        foreach ($this->getUpvotes() as $upvote) {
            if ($upvote->getUser()->getId() === $user->getId()) {
                $this->setUserHasUpvoted(true);
                break;
            }
        }

        return $this;
    }

    /**
     * Get createdByCurrentUser
     *
     * @return bool
     */
    public function isCreatedByCurrentUser(): bool
    {
        return $this->createdByCurrentUser;
    }

    /**
     * Add upvote
     *
     * @param CommentUpvote $upvote
     *
     * @return Comment
     */
    public function addUpvote(CommentUpvote $upvote): Comment
    {
        $this->upvotes[] = $upvote;

        return $this;
    }

    /**
     * Remove upvote
     *
     * @param CommentUpvote $upvote
     */
    public function removeUpvote(CommentUpvote $upvote): Comment
    {
        $this->upvotes->removeElement($upvote);

        return $this;
    }

    /**
     * Get upvotes
     *
     * @return Collection
     */
    public function getUpvotes(): Collection
    {
        return $this->upvotes;
    }

    /**
     * Get upvoteCount
     *
     * @return int
     */
    public function getUpvoteCount(): int
    {
        return $this->upvotes->count();
    }

    /**
     * Set userHasUpvoted
     *
     * @param bool $userHasUpvoted
     *
     * @return Comment
     */
    public function setUserHasUpvoted(bool $userHasUpvoted): Comment
    {
        $this->userHasUpvoted = $userHasUpvoted;

        return $this;
    }

    /**
     * Get userHasUpvoted
     *
     * @return bool
     */
    public function hasUserUpvoted(): bool
    {
        return $this->userHasUpvoted;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Comment
     */
    public function setCreatedAt(\DateTime $createdAt): Comment
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Comment
     */
    public function setUpdatedAt(\DateTime $updatedAt): Comment
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}
