<?php

namespace Salt\SiteBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Salt\UserBundle\Entity\User;

/**
 * CommentRepository
 *
 * @method Comment[] findByItem(string $itemRef)
 */
class CommentRepository extends EntityRepository
{
    /**
     * @param string $itemType
     * @param int $itemId
     * @param User $user
     * @param string $content
     * @param int $parentId
     *
     * @return Comment
     */
    public function addComment($itemType, $itemId, User $user, string $content, $parentId = null)
    {
        $comment = new Comment();
        $comment->setContent(trim($content));
        $comment->setUser($user);
        $comment->setItem($itemType.':'.$itemId);
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
        return $this->findByItem($id['itemType'].':'.$id['itemId']);
    }
}
