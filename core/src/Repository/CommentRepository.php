<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment\Comment;
use App\Entity\Comment\CommentUpvote;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method list<Comment> findByItem(string $itemRef)
 *
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    /**
     * Maps item type strings to their corresponding entity classes.
     * The keys also correspond to the association field names on the Comment entity.
     */
    private const array ITEM_TYPE_ENTITY_MAP = [
        'document' => LsDoc::class,
        'item' => LsItem::class,
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function addComment(string $itemType, LsItem|LsDoc $itemId, User $user, ?string $content = null, ?string $fileUrl = null, ?string $mimeType = null, ?int $parentId = null): Comment
    {
        $comment = new Comment($user, trim($content));
        $comment->setFileUrl($fileUrl);
        $comment->setFileMimeType($mimeType);

        if ('item' === $itemType) {
            $comment->setItem($itemId);
        } else {
            $comment->setDocument($itemId);
        }

        $comment->setCreatedByCurrentUser(true);

        $parent = null;
        if (null !== $parentId && $parentId > 0) {
            $parent = $this->find($parentId);
        }
        $comment->setParent($parent);

        $this->getEntityManager()->persist($comment);

        return $comment;
    }

    public function addUpvoteForUser(Comment $comment, User $user): CommentUpvote
    {
        $commentUpvote = new CommentUpvote($user, $comment);

        $this->getEntityManager()->persist($commentUpvote);

        return $commentUpvote;
    }

    public function removeUpvoteForUser(Comment $comment, User $user): bool
    {
        $em = $this->getEntityManager();

        $commentUpvote = $em->getRepository(CommentUpvote::class)
            ->findOneBy(['user' => $user, 'comment' => $comment]);

        if (null !== $commentUpvote) {
            $em->remove($commentUpvote);

            return true;
        }

        return false;
    }

    /**
     * Find comments for a given item type and item identifier.
     *
     * The $itemId can be either a numeric database ID or a UUID identifier string.
     * When numeric, comments are queried directly by foreign key (no entity lookup needed).
     * When a UUID, the entity is resolved first via its identifier field.
     *
     * @return ArrayCollection<int, Comment>
     *
     * @throws \InvalidArgumentException if $itemType is not 'document' or 'item'
     */
    public function findByTypeId(string $itemType, string $itemId): ArrayCollection
    {
        $entityClass = self::ITEM_TYPE_ENTITY_MAP[$itemType]
            ?? throw new \InvalidArgumentException(sprintf('Unsupported item type: "%s". Expected one of: %s', $itemType, implode(', ', array_keys(self::ITEM_TYPE_ENTITY_MAP))));

        // Numeric ID — query comments directly by FK, no entity lookup needed
        if (ctype_digit($itemId)) {
            return new ArrayCollection($this->findBy([$itemType => (int) $itemId]));
        }

        // UUID identifier — resolve entity first, then query by association
        $entity = $this->getEntityManager()->getRepository($entityClass)
            ->findOneBy(['identifier' => $itemId]);

        if (null === $entity) {
            return new ArrayCollection();
        }

        return new ArrayCollection($this->findBy([$itemType => $entity]));
    }
}
