<?php

namespace Salt\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Salt\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * CommentUpvote
 *
 * @ORM\Entity
 * @ORM\Table(name="salt_comment_upvote", uniqueConstraints={@ORM\UniqueConstraint(name="comment_user", columns={"comment_id", "user_id"})})
 * @UniqueEntity(fields={"comment", "user"})
 */
class CommentUpvote
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
     * @ORM\ManyToOne(targetEntity="Comment", inversedBy="upvotes")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $comment;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="\Salt\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", precision=6)
     * @Serializer\SerializedName("created")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", precision=6)
     * @Serializer\SerializedName("modified")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

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
     * Set user
     *
     * @param User $user
     *
     * @return CommentUpvote
     */
    public function setUser(User $user): CommentUpvote
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
     * Set comment
     *
     * @param Comment $comment
     *
     * @return CommentUpvote
     */
    public function setComment(Comment $comment = null): CommentUpvote
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return Comment
     */
    public function getComment(): Comment
    {
        return $this->comment;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return CommentUpvote
     */
    public function setCreatedAt(\DateTime $createdAt): CommentUpvote
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
     * @return CommentUpvote
     */
    public function setUpdatedAt(\DateTime $updatedAt): CommentUpvote
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
