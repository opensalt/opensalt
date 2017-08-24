<?php

namespace Salt\SiteBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CommentRepository
 *
 * @method Comment[] findByItem(string $itemRef)
 */
class CommentRepository extends EntityRepository
{
    /**
     * @param array $id
     * @return array|Comment[]
     */
    public function findByTypeItem(array $id): array
    {
        return $this->findByItem($id['itemType'].':'.$id['itemId']);
    }
}
