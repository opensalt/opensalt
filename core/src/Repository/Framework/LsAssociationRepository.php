<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;

/**
 * @method LsAssociation|null findOneByIdentifier(string $identifier)
 *
 * @extends ServiceEntityRepository<LsAssociation>
 */
class LsAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsAssociation::class);
    }

    public function removeAssociation(LsAssociation $lsAssociation): void
    {
        $this->getEntityManager()->remove($lsAssociation);
        $origin = $lsAssociation->getOrigin();
        if (is_object($origin)) {
            $origin->removeAssociation($lsAssociation);
        }
        $dest = $lsAssociation->getDestination();
        if (is_object($dest)) {
            $dest->removeInverseAssociation($lsAssociation);
        }
    }

    /**
     * Remove all associations from the object.
     */
    public function removeAllAssociations(LsItem|LsDoc $object): void
    {
        foreach ($object->getAssociations() as $association) {
            $this->removeAssociation($association);
        }
        foreach ($object->getInverseAssociations() as $association) {
            $this->removeAssociation($association);
        }
    }

    /**
     * Remove all associations of a specific type from the object.
     *
     * @return LsAssociation[]
     */
    public function removeAllAssociationsOfType(LsItem|LsDoc $object, string $type): array
    {
        $deleted = [];
        foreach ($object->getAssociations() as $association) {
            if ($association->getType() === $type) {
                $this->removeAssociation($association);
                $deleted[] = $association;
            }
        }

        return $deleted;
    }

    /**
     * @param list<string> $types
     *
     * @return list<LsAssociation>
     */
    public function findByLsDocAndTypes(LsDoc $doc, array $types): array
    {
        if ([] === $types) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('a.lsDoc = :doc')
            ->andWhere('a.type IN (:types)')
            ->setParameter('doc', $doc)
            ->setParameter('types', $types)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<int>
     */
    public function findArticulationDocIdsForInstitutionItems(
        LsItem $sendingOrg,
        LsItem $receivingOrg,
    ): array {
        $sendingOrgId = $sendingOrg->getId();
        $sendingIdentifier = $sendingOrg->getIdentifier();
        $receivingOrgId = $receivingOrg->getId();
        $receivingIdentifier = $receivingOrg->getIdentifier();

        if (
            null === $sendingOrgId
            || null === $receivingOrgId
            || null === $sendingIdentifier
            || '' === $sendingIdentifier
            || null === $receivingIdentifier
            || '' === $receivingIdentifier
        ) {
            return [];
        }

        // Prefer destination_* predicates so MySQL can use dest_id_idx / destination_lsitem_id
        // instead of a full scan on association type (no type index).
        $sql = <<<'SQL'
            SELECT DISTINCT sd.ls_doc_id AS docId
            FROM ls_association sd
            INNER JOIN ls_association rd ON sd.ls_doc_id = rd.ls_doc_id
            WHERE (
                sd.destination_lsitem_id = :sendingOrgId
                OR sd.destination_node_identifier = :sendingIdentifier
              )
              AND sd.type = :sendingType
              AND (
                rd.destination_lsitem_id = :receivingOrgId
                OR rd.destination_node_identifier = :receivingIdentifier
              )
              AND rd.type = :receivingType
            SQL;

        /** @var list<array{docId: string|int}> $rows */
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            $sql,
            [
                'sendingType' => 'ext:sendingInstitution',
                'receivingType' => 'ext:receivingInstitution',
                'sendingOrgId' => $sendingOrgId,
                'sendingIdentifier' => $sendingIdentifier,
                'receivingOrgId' => $receivingOrgId,
                'receivingIdentifier' => $receivingIdentifier,
            ],
            [
                'sendingOrgId' => ParameterType::INTEGER,
                'receivingOrgId' => ParameterType::INTEGER,
            ],
        );

        return array_map(static fn (array $row): int => (int) $row['docId'], $rows);
    }

    /**
     * @return list<int>
     */
    public function findDocIdsByAssociationType(string $type): array
    {
        /** @var list<array{docId: string|int}> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT IDENTITY(a.lsDoc) AS docId')
            ->where('a.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['docId'], $rows);
    }

    /**
     * @return LsAssociation[]
     */
    public function findAllChildAssociationsFor(string $identifier): array
    {
        $qry = $this->createQueryBuilder('a')
            ->where('a.destinationNodeIdentifier = :identifier')
            ->andWhere('a.type = :type')
            ->setParameter('identifier', $identifier)
            ->setParameter('type', LsAssociation::CHILD_OF)
            ->getQuery();

        return $qry->getResult();
    }

    /**
     * @return LsAssociation[]
     */
    public function findAllAssociationsFor(string $identifier): array
    {
        try {
            $uuid = Uuid::fromString(str_replace('_', '', $identifier))->toString();
        } catch (\Throwable) {
            return [];
        }

        $item = $this->getEntityManager()->getRepository(LsItem::class)
            ->findOneBy(['identifier' => $uuid]);

        if (null === $item) {
            return [];
        }

        $qry = $this->createQueryBuilder('a')
            ->where('a.originLsItem = :id')
            ->orWhere('a.destinationLsItem = :id')
            ->orWhere('a.originNodeIdentifier = :uuid')
            ->orWhere('a.destinationNodeIdentifier = :uuid')
            ->setParameter('id', $item->getId())
            ->setParameter('uuid', $uuid)
            ->getQuery();

        return $qry->getResult();
    }

    public function findAllAssociationsForAsSplitArray(string $identifier): array
    {
        try {
            $uuid = Uuid::fromString(str_replace('_', '', $identifier))->toString();
        } catch (\Throwable) {
            return ['associations' => [], 'inverseAssociations' => []];
        }

        $associations = $this->findAllAssociationsFor($uuid);
        $forward = [];
        $reverse = [];

        foreach ($associations as $association) {
            if ($association->getOriginNodeIdentifier() === $uuid) {
                $forward[] = $association;
            } else {
                $reverse[] = $association;
            }
        }

        return ['associations' => $forward, 'inverseAssociations' => $reverse];
    }

    /**
     * @param string[] $identifiers
     *
     * @return LsAssociation[]
     */
    public function findByIdentifiers(array $identifiers, LsDoc $doc): array
    {
        if ([] === $identifiers) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers));
        $qb->andWhere('t.lsDoc = :docId')
            ->setParameter('docId', $doc->getId())
        ;
        $qb->orderBy('t.sequenceNumber', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @phpstan-return array{total: int, items: array<int, array{0: LsAssociation, origin_human_coding_scheme: string|null, origin_abbreviated_statement: string|null, origin_full_statement: string|null, destination_human_coding_scheme: string|null, destination_abbreviated_statement: string|null, destination_full_statement: string|null}>}
     */
    public function findForItem(
        string $itemIdentifier,
        ?string $frameworkId = null,
        int $limit = 1000,
        int $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.originLsItem', 'i1')
            ->leftJoin('a.destinationLsItem', 'i2')
            ->where('a.originNodeIdentifier = :itemId OR a.destinationNodeIdentifier = :itemId')
            ->setParameter('itemId', $itemIdentifier)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('a.sequenceNumber', 'ASC');

        if ($frameworkId) {
            $qb->andWhere('a.lsDocIdentifier = :frameworkId')
               ->setParameter('frameworkId', $frameworkId);
        }

        $qb->select('a, i1.humanCodingScheme as origin_human_coding_scheme, i1.abbreviatedStatement as origin_abbreviated_statement, i1.fullStatement as origin_full_statement,
                          i2.humanCodingScheme as destination_human_coding_scheme, i2.abbreviatedStatement as destination_abbreviated_statement, i2.fullStatement as destination_full_statement');

        $total = (clone $qb)->select('COUNT(DISTINCT a.id)')->getQuery()->getSingleScalarResult();
        $items = $qb->getQuery()->getResult();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * @phpstan-return array{total: int, items: array<int, array{0: LsAssociation, origin_human_coding_scheme: string|null, origin_abbreviated_statement: string|null, origin_full_statement: string|null, destination_human_coding_scheme: string|null, destination_abbreviated_statement: string|null, destination_full_statement: string|null}>}
     */
    public function findByDocument(
        LsDoc|string $docId,
        int $limit = 1000,
        int $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.originLsItem', 'i1')
            ->leftJoin('a.destinationLsItem', 'i2')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('a.sequenceNumber', 'ASC');

        if ($docId instanceof LsDoc) {
            $qb->where('a.lsDoc = :docId');
        } else {
            $qb->where('a.lsDocIdentifier = :docId');
        }
        $qb->setParameter('docId', $docId);

        $qb->select('a, i1.humanCodingScheme as origin_human_coding_scheme, i1.abbreviatedStatement as origin_abbreviated_statement, i1.fullStatement as origin_full_statement,
                          i2.humanCodingScheme as destination_human_coding_scheme, i2.abbreviatedStatement as destination_abbreviated_statement, i2.fullStatement as destination_full_statement');

        $total = (clone $qb)->select('COUNT(DISTINCT a.id)')->getQuery()->getSingleScalarResult();
        $items = $qb->getQuery()->getResult();

        return ['total' => $total, 'items' => $items];
    }

    /**
     * @phpstan-return array{total: int, items: array<int, array{0: LsAssociation, origin_human_coding_scheme: string|null, origin_abbreviated_statement: string|null, origin_full_statement: string|null, destination_human_coding_scheme: string|null, destination_abbreviated_statement: string|null, destination_full_statement: string|null}>}
     */
    public function findAllForFramework(string $docIdentifier, int $limit = 1000, int $offset = 0): array
    {
        $em = $this->getEntityManager();

        $doc = $em->getRepository(LsDoc::class)->findOneBy(['identifier' => $docIdentifier]);
        if (null === $doc) {
            return ['total' => 0, 'items' => []];
        }

        $itemIdentifiers = $em->getRepository(LsItem::class)->findIdentifiersByLsDoc($doc);
        $allIdentifiers = array_merge([$docIdentifier], $itemIdentifiers);

        $selectFields = 'a, i1.humanCodingScheme as origin_human_coding_scheme, i1.abbreviatedStatement as origin_abbreviated_statement, i1.fullStatement as origin_full_statement, i2.humanCodingScheme as destination_human_coding_scheme, i2.abbreviatedStatement as destination_abbreviated_statement, i2.fullStatement as destination_full_statement';

        $results1 = $this->createQueryBuilder('a')
            ->leftJoin('a.originLsItem', 'i1')
            ->leftJoin('a.destinationLsItem', 'i2')
            ->where('a.lsDoc = :doc')
            ->setParameter('doc', $doc->getId())
            ->orderBy('a.sequenceNumber', 'ASC')
            ->select($selectFields)
            ->getQuery()
            ->getResult();

        $results2 = $this->createQueryBuilder('a')
            ->leftJoin('a.originLsItem', 'i1')
            ->leftJoin('a.destinationLsItem', 'i2')
            ->where('a.originNodeIdentifier IN (:ids)')
            ->setParameter('ids', $allIdentifiers)
            ->orderBy('a.sequenceNumber', 'ASC')
            ->select($selectFields)
            ->getQuery()
            ->getResult();

        $results3 = $this->createQueryBuilder('a')
            ->leftJoin('a.originLsItem', 'i1')
            ->leftJoin('a.destinationLsItem', 'i2')
            ->where('a.destinationNodeIdentifier IN (:ids)')
            ->setParameter('ids', $allIdentifiers)
            ->orderBy('a.sequenceNumber', 'ASC')
            ->select($selectFields)
            ->getQuery()
            ->getResult();

        $merged = [];
        $seen = [];
        foreach (array_merge($results1, $results2, $results3) as $row) {
            $assocEntity = $row[0];
            $id = $assocEntity->getId();
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                $merged[] = $row;
            }
        }

        $total = count($merged);
        $items = array_slice($merged, $offset, $limit > 0 ? $limit : null);

        return ['total' => $total, 'items' => $items];
    }
}
