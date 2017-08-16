<?php

namespace Salt\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;

/**
 * Comment
 *
 * @ORM\Entity
 * @ORM\Table(name="salt_comment")
 */
class Comment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $parent;

    /**
     * @ORM\Column(type="string")
     */
    private $content;

    /**
     * @Serializer\Exclude()
     * @ORM\ManyToOne(targetEntity="\Salt\UserBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     */
    private $item;

    /**
     * @ORM\Column(type="string")
     */
    private $fullname;

    /**
     * @ORM\OneToMany(targetEntity="CommentUpvote", mappedBy="comment")
     * @Serializer\Accessor(getter="getUpvoteCount")
     * @Serializer\ReadOnly
     * @Serializer\Type("int")
     * @Serializer\SerializedName("upvote_count")
     */
    private $upvotes;

    /**
     * @ORM\Column(type="datetime", columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL")
     * @Serializer\SerializedName("created")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL")
     * @Serializer\SerializedName("modified")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    private $createdByCurrentUser;
    private $userHasUpvoted;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->upvotes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @param int $id
     *
     * @return Comment
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
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
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return int
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Comment
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set user
     *
     * @param \Salt\UserBundle\Entity\User $userId
     *
     * @return Comment
     */
    public function setUser(\Salt\UserBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Salt\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set item
     *
     * @param int $item
     *
     * @return Comment
     */
    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return int
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     *
     * @return Comment
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set createdByCurrentUser
     *
     * @param string $createdByCurrentUser
     *
     * @return Comment
     */
    public function setCreatedByCurrentUser($createdByCurrentUser)
    {
        $this->createdByCurrentUser = $createdByCurrentUser;

        return $this;
    }

    /**
     * Get createdByCurrentUser
     *
     * @return string
     */
    public function getCreatedByCurrentUser()
    {
        return $this->createdByCurrentUser;
    }

    /**
     * Add upvote
     *
     * @param \Salt\SiteBundle\Entity\CommentUpvote $upvote
     *
     * @return Comment
     */
    public function addUpvote(\Salt\SiteBundle\Entity\CommentUpvote $upvote)
    {
        $this->upvotes[] = $upvote;

        return $this;
    }

    /**
     * Remove upvote
     *
     * @param \Salt\SiteBundle\Entity\CommentUpvote $upvote
     */
    public function removeUpvote(\Salt\SiteBundle\Entity\CommentUpvote $upvote)
    {
        $this->upvotes->removeElement($upvote);
    }

    /**
     * Get upvotes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUpvotes()
    {
        return $this->upvotes;
    }

    /**
     * Get upvoteCount
     *
     * @return int
     */
    public function getUpvoteCount()
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
    public function setUserHasUpvoted($userHasUpvoted)
    {
        $this->userHasUpvoted = $userHasUpvoted;

        return $this;
    }

    /**
     * Get userHasUpvoted
     *
     * @return bool
     */
    public function hasUserUpvoted()
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
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
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
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
