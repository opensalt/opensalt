<?php

namespace App\Repository;

use App\Entity\Comment\Comment;
use App\Entity\Comment\CommentUpvote;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comment[] findByItem(string $itemRef)
 * @method Comment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function addComment(string $itemType, LsItem|LsDoc $itemId, User $user, ?string $content = null, ?string $fileUrl = null, ?string $mimeType = null, ?int $parentId = null): Comment
    {
        $comment = new Comment();
        $comment->setContent(trim($content));
        $comment->setFileUrl($fileUrl);
        $comment->setFileMimeType($mimeType);
        $comment->setUser($user);

        if ('item' === $itemType) {
            $comment->setItem($itemId);
        } else {
            $comment->setDocument($itemId);
        }

        $comment->setCreatedByCurrentUser(true);

        $parent = $this->find($parentId);
        $comment->setParent($parent);

        $this->getEntityManager()->persist($comment);

        return $comment;
    }

    public function addUpvoteForUser(Comment $comment, User $user): CommentUpvote
    {
        $commentUpvote = new CommentUpvote();
        $commentUpvote->setComment($comment);
        $commentUpvote->setUser($user);

        $this->getEntityManager()->persist($commentUpvote);

        return $commentUpvote;
    }

    public function removeUpvoteForUser(Comment $comment, User $user): bool
    {
        $em = $this->getEntityManager();

        $commentUpvote = $em->getRepository(CommentUpvote::class)
            ->findOneBy(['user' => $user, 'comment' => $comment]);

        if ($commentUpvote) {
            $em->remove($commentUpvote);

            return true;
        }

        return false;
    }

    /**
     * @return Collection<array-key, Comment>
     */
    public function findByTypeItem(string $itemType, int $itemId): Collection
    {
        return new ArrayCollection($this->findBy([$itemType => $itemId]));
    }
}
