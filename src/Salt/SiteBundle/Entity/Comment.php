<?php

namespace Salt\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Comment
 *
 * @ORM\Entity
 * @ORM\Table(name="comments")
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
     * @ORM\Column(type="string")
     */
    private $commentId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $parent;

    /**
     * @ORM\Column(type="string")
     */
    private $content;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Exclude()
     */
    private $userId;

    /**
     * @ORM\Column(type="string")
     */
    private $item;

    /**
     * @ORM\Column(type="string")
     */
    private $fullname;

    /**
     * @ORM\Column(type="boolean")
     */
    private $createdByCurrentUser = false;

    /**
     * @ORM\Column(type="integer")
     */
    private $upvoteCount;

    /**
     * @ORM\Column(type="boolean")
     */
    private $userHasUpvoted;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\SerializedName("created")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\SerializedName("modified")
     */
    private $updatedAt;

    /**
     * Set id
     *
     * @param integer $id
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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set commentId
     *
     * @param string $commentId
     *
     * @return Comment
     */
    public function setCommentId($commentId)
    {
        $this->commentId = $commentId;

        return $this;
    }

    /**
     * Get commentId
     *
     * @return string
     */
    public function getCommentId()
    {
        return $this->commentId;
    }

    /**
     * Set parent
     *
     * @param integer $parent
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
     * @return integer
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return Comment
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set item
     *
     * @param integer $item
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
     * @return integer
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
     * Set upvoteCount
     *
     * @param integer $upvoteCount
     *
     * @return Comment
     */
    public function setUpvoteCount($upvoteCount)
    {
        $this->upvoteCount = $upvoteCount;

        return $this;
    }

    /**
     * Get upvoteCount
     *
     * @return integer
     */
    public function getUpvoteCount()
    {
        return $this->upvoteCount;
    }

    /**
     * Set userHasUpvoted
     *
     * @param boolean $userHasUpvoted
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
     * @return boolean
     */
    public function getUserHasUpvoted()
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
