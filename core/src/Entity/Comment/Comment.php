<?php

namespace App\Entity\Comment;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

/**
 * Comment
 *
 * @ORM\Entity(repositoryClass="App\Repository\CommentRepository")
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
     * @ORM\ManyToOne(targetEntity="Comment")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     *
     * @Serializer\Accessor(getter="getParentId")
     * @Serializer\ReadOnly
     * @Serializer\Type("int")
     */
    private ?Comment $parent = null;

    /**
     * @ORM\Column(type="text")
     */
    private string $content;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Serializer\Exclude()
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Framework\LsDoc")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private ?LsDoc $document = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Framework\LsItem")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private ?LsItem $item = null;

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
     * @ORM\Column(type="datetime", precision=6)
     * @Serializer\SerializedName("created")
     * @Gedmo\Timestampable(on="create")
     */
    private \DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime", precision=6)
     * @Serializer\SerializedName("modified")
     * @Gedmo\Timestampable(on="update")
     */
    private \DateTime $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $fileUrl = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $fileMimeType = null;

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
        $this->upvotes = new ArrayCollection();
    }

    /**
     * Get id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set parent
     */
    public function setParent(Comment $parent = null): Comment
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     */
    public function getParent(): ?Comment
    {
        return $this->parent;
    }

    /**
     * Get the id of a parent
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
     */
    public function setContent(string $content): Comment
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set user
     */
    public function setUser(User $user): Comment
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set item
     */
    public function setItem(LsItem $item): Comment
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     */
    public function getItem(): ?LsItem
    {
        return $this->item;
    }

    /**
     * Set document
     */
    public function setDocument(LsDoc $document): Comment
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Get document
     */
    public function getDocument(): ?LsDoc
    {
        return $this->document;
    }

    /**
     * Get fullname
     */
    public function getFullname(): string
    {
        return preg_replace('/@.*/', '', $this->getUser()->getUsername());
    }

    /**
     * Set createdByCurrentUser
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
     */
    public function isCreatedByCurrentUser(): bool
    {
        return $this->createdByCurrentUser;
    }

    /**
     * Add upvote
     */
    public function addUpvote(CommentUpvote $upvote): Comment
    {
        $this->upvotes[] = $upvote;

        return $this;
    }

    /**
     * Remove upvote
     */
    public function removeUpvote(CommentUpvote $upvote): Comment
    {
        $this->upvotes->removeElement($upvote);

        return $this;
    }

    /**
     * Get upvotes
     */
    public function getUpvotes(): Collection
    {
        return $this->upvotes;
    }

    /**
     * Get upvoteCount
     */
    public function getUpvoteCount(): int
    {
        return $this->upvotes->count();
    }

    /**
     * Set userHasUpvoted
     */
    public function setUserHasUpvoted(bool $userHasUpvoted): Comment
    {
        $this->userHasUpvoted = $userHasUpvoted;

        return $this;
    }

    /**
     * Get userHasUpvoted
     */
    public function hasUserUpvoted(): bool
    {
        return $this->userHasUpvoted;
    }

    /**
     * Set createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): Comment
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): Comment
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set fileUrl
     */
    public function setFileUrl(?string $fileUrl): Comment
    {
        $this->fileUrl = $fileUrl;

        return $this;
    }

    /**
     * Get fileUrl
     */
    public function getFileUrl(): ?string
    {
        return $this->fileUrl;
    }

    /**
     * Set fileMimeType
     */
    public function setFileMimeType(?string $fileMimeType): Comment
    {
        $this->fileMimeType = $fileMimeType;

        return $this;
    }

    /**
     * Get fileMimeType
     */
    public function getFileMimeType(): string
    {
        return $this->fileMimeType;
    }
}
