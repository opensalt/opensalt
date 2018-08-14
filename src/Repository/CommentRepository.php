<?php

namespace App\Repository;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\Comment\Comment;
use App\Entity\Comment\CommentUpvote;
use App\Entity\User\User;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * CommentRepository
 *
 * @method Comment[] findByItem(string $itemRef)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @param string $itemType
     * @param LsDoc|LsItem $itemId
     * @param User $user
     * @param string $content
     * @param int $parentId
     *
     * @return Comment
     */
    public function addComment(string $itemType, $itemId, User $user, ?string $content = null, ?string $fileUrl = null, ?string $mimeType = null, $parentId = null)
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
     * @param array $id
     *
     * @return array|Comment[]
     */
    public function findByTypeItem(array $id): array
    {
        return $this->findBy([$id['itemType'] => $id['itemId']]);
    }
}
