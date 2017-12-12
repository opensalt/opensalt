<?php

namespace CftfBundle\Repository;

use CftfBundle\Entity\LsDefItemType;

/**
 * LsDefItemTypeRepository
 *
 * @method null|LsDefItemType findOneByTitle(string $title)
 */
class LsDefItemTypeRepository extends AbstractLsDefinitionRepository
{
    /**
     * @return array|LsDefItemType[]
     */
    public function getList()
    {
        $qb = $this->createQueryBuilder('t', 't.code')
            ->orderBy('t.code')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string|null $search
     *
     * @return array|LsDefItemType[]
     */
    public function getSelect2List($search = null, $limit = 50, $page = 1): array
    {
        // NOTE: indexing by title makes there only be one value per title
        // this should be changed to handle the doc or something
        $qb = $this->createQueryBuilder('t', 't.title')
            ->orderBy('t.title')
            ->setMaxResults($limit+1)
            ->setFirstResult(($page - 1) * $limit)
        ;

        if (!empty($search)) {
            $qb->andWhere('t.title LIKE :search')
                ->setParameter('search', '%'.$search.'%')
                ;
        }

        /** @var LsDefItemType[] $results */
        $results = $qb->getQuery()->getResult();

        if (count($results) > $limit) {
            $more = true;
            array_pop($results);
        } else {
            $more = false;
        }

        return ['results' => $results, 'more' => $more];
    }
}
