<?php

declare(strict_types=1);

namespace App\VectorSearch\Repository;

use App\VectorSearch\Entity\LsItemEmbedding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LsItemEmbedding>
 */
class LsItemEmbeddingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsItemEmbedding::class);
    }

    /**
     * Find embeddings by LsItem ID.
     *
     * @param int $lsItemId The LsItem ID
     * @return array<LsItemEmbedding>
     */
    public function findByLsItemId(int $lsItemId): array
    {
        return $this->findBy(['lsItem' => $lsItemId]);
    }

    /**
     * Delete embeddings by LsItem ID.
     *
     * @param int $lsItemId The LsItem ID
     * @return int Number of deleted embeddings
     */
    public function deleteByLsItemId(int $lsItemId): int
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->delete()
            ->where($queryBuilder->expr()->eq('e.lsItem', ':lsItemId'))
            ->setParameter('lsItemId', $lsItemId);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param list<int> $ids
     * @return array<int, LsItemEmbedding>
     */
    public function findByIdsWithLsItemIndexed(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder
            ->addSelect('li')
            ->innerJoin('e.lsItem', 'li')
            ->where($queryBuilder->expr()->in('e.id', ':ids'))
            ->setParameter('ids', $ids);

        /** @var list<LsItemEmbedding> $embeddings */
        $embeddings = $queryBuilder->getQuery()->getResult();

        $indexed = [];
        foreach ($embeddings as $embedding) {
            $embeddingId = $embedding->getId();
            if (null !== $embeddingId) {
                $indexed[$embeddingId] = $embedding;
            }
        }

        return $indexed;
    }

    /**
     * @param list<int> $lsItemIds
     * @return array<int, LsItemEmbedding>
     */
    public function findByLsItemIdsIndexed(array $lsItemIds): array
    {
        if ([] === $lsItemIds) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder
            ->addSelect('li')
            ->innerJoin('e.lsItem', 'li')
            ->where($queryBuilder->expr()->in('li.id', ':lsItemIds'))
            ->setParameter('lsItemIds', $lsItemIds);

        /** @var list<LsItemEmbedding> $embeddings */
        $embeddings = $queryBuilder->getQuery()->getResult();

        $indexed = [];
        foreach ($embeddings as $embedding) {
            $lsItemId = $embedding->getLsItem()->getId();
            if (null !== $lsItemId) {
                $indexed[$lsItemId] = $embedding;
            }
        }

        return $indexed;
    }

    /**
     * @param list<int> $lsItemIds
     * @return array<int, array{hasVectorData: bool, sourceHierarchyUpdatedAt: \DateTimeImmutable|null}>
     */
    public function getEmbeddingStatusByLsItemIds(array $lsItemIds): array
    {
        if ([] === $lsItemIds) {
            return [];
        }

        $rows = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select(
                'ls_item_id',
                'source_hierarchy_updated_at',
                '(vector IS NOT NULL AND normalized_vector IS NOT NULL AND magnitude IS NOT NULL AND binary_code IS NOT NULL) AS has_vector_data'
            )
            ->from('ls_item_embedding')
            ->where('ls_item_id IN (:lsItemIds)')
            ->setParameter('lsItemIds', $lsItemIds, \Doctrine\DBAL\ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $indexed = [];
        foreach ($rows as $row) {
            $sourceHierarchyUpdatedAt = null !== $row['source_hierarchy_updated_at']
                ? new \DateTimeImmutable((string) $row['source_hierarchy_updated_at'])
                : null;

            $indexed[(int) $row['ls_item_id']] = [
                'hasVectorData' => (bool) $row['has_vector_data'],
                'sourceHierarchyUpdatedAt' => $sourceHierarchyUpdatedAt,
            ];
        }

        return $indexed;
    }
}
