<?php

declare(strict_types=1);

namespace App\Repository\System;

use App\Entity\System\ApiUsageLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApiUsageLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiUsageLog::class);
    }

    /**
     * Legacy offset pagination (kept for compatibility, no longer used by UI).
     *
     * @return ApiUsageLog[]
     */
    public function filter(
        ?string $userIdentifier,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        ?string $endpointLike,
        int $limit = 200,
        int $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if (null !== $userIdentifier && '' !== $userIdentifier) {
            $qb->andWhere('l.userIdentifier = :uid')
               ->setParameter('uid', $userIdentifier);
        }

        if (null !== $from) {
            $qb->andWhere('l.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if (null !== $to) {
            $qb->andWhere('l.createdAt <= :to')
               ->setParameter('to', $to);
        }

        if (null !== $endpointLike && '' !== $endpointLike) {
            $qb->andWhere('LOWER(l.endpointUrl) LIKE :endpoint')
               ->setParameter('endpoint', '%'.mb_strtolower($endpointLike).'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Cursor-based pagination. Order is always newest to oldest: createdAt DESC, id DESC.
     * Only one of (afterTs, afterId) or (beforeTs, beforeId) should be provided at a time.
     *
     * @return ApiUsageLog[]
     */
    public function filterByCursor(
        ?string $userIdentifier,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        ?string $endpointLike,
        ?\DateTimeImmutable $afterTs,
        ?int $afterId,
        ?\DateTimeImmutable $beforeTs,
        ?int $beforeId,
        int $limit = 50,
    ): array {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setMaxResults($limit);

        if (null !== $userIdentifier && '' !== $userIdentifier) {
            $qb->andWhere('l.userIdentifier = :uid')
               ->setParameter('uid', $userIdentifier);
        }

        if (null !== $from) {
            $qb->andWhere('l.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if (null !== $to) {
            $qb->andWhere('l.createdAt <= :to')
               ->setParameter('to', $to);
        }

        if (null !== $endpointLike && '' !== $endpointLike) {
            $qb->andWhere('LOWER(l.endpointUrl) LIKE :endpoint')
               ->setParameter('endpoint', '%'.mb_strtolower($endpointLike).'%');
        }

        // Apply cursor conditions
        if (null !== $afterTs && null !== $afterId) {
            // Fetch items STRICTLY older than the cursor (for "next" page)
            $qb->andWhere('(l.createdAt < :ats) OR (l.createdAt = :ats AND l.id < :aid)')
               ->setParameter('ats', $afterTs)
               ->setParameter('aid', $afterId);
        } elseif (null !== $beforeTs && null !== $beforeId) {
            // Fetch items STRICTLY newer than the cursor (for "prev" page)
            $qb->andWhere('(l.createdAt > :bts) OR (l.createdAt = :bts AND l.id > :bid)')
               ->setParameter('bts', $beforeTs)
               ->setParameter('bid', $beforeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Return a list of distinct user identifiers present in the logs (for filter UI).
     *
     * @return string[]
     */
    public function distinctUserIdentifiers(): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('DISTINCT l.userIdentifier AS uid')
            ->orderBy('l.userIdentifier', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $r) => $r['uid'], $rows);
    }
}
