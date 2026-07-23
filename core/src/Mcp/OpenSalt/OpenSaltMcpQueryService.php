<?php

declare(strict_types=1);

namespace App\Mcp\OpenSalt;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use Doctrine\ORM\QueryBuilder;

readonly class OpenSaltMcpQueryService
{
    public function __construct(
        private LsDocRepository $documentRepository,
        private LsItemRepository $itemRepository,
    ) {
    }

    /**
     * @return list<LsDoc>
     */
    public function listPublicDocuments(int $limit = 25, int $offset = 0, ?string $query = null): array
    {
        $limit = $this->normalizeLimit($limit, 25, 100);
        $offset = max(0, $offset);

        $conn = $this->documentRepository->getEntityManager()->getConnection();
        $sql = 'SELECT d.id
                FROM ls_doc d
                STRAIGHT_JOIN mirror_framework m ON m.id = d.mirrored_framework_id
                WHERE (d.adoption_status IS NULL OR d.adoption_status <> :privateDraft)
                  AND (m.visible IS NULL OR m.visible = 1)';
        $params = ['privateDraft' => LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT];
        $types = ['privateDraft' => \PDO::PARAM_STR];

        if (null !== $query && '' !== trim($query)) {
            $like = '%'.strtolower(trim($query)).'%';
            $sql .= ' AND (LOWER(d.title) LIKE :q1 OR LOWER(d.creator) LIKE :q2 OR LOWER(d.publisher) LIKE :q3 OR LOWER(d.identifier) LIKE :q4)';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $types['q1'] = \PDO::PARAM_STR;
            $types['q2'] = \PDO::PARAM_STR;
            $types['q3'] = \PDO::PARAM_STR;
            $types['q4'] = \PDO::PARAM_STR;
        }

        $sql .= ' ORDER BY d.changed_at DESC, d.id DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        $types['limit'] = \PDO::PARAM_INT;
        $types['offset'] = \PDO::PARAM_INT;

        $ids = $conn->executeQuery($sql, $params, $types)->fetchFirstColumn();

        if ([] === $ids) {
            return [];
        }

        $documents = $this->documentRepository->createQueryBuilder('d')
            ->select('d')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($documents as $document) {
            $byId[$document->getId()] = $document;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function getPublicDocumentByIdentifier(string $documentIdentifier): ?LsDoc
    {
        $qb = $this->documentRepository->createQueryBuilder('d')
            ->select('d')
            ->andWhere('d.identifier = :identifier')
            ->setParameter('identifier', trim($documentIdentifier))
            ->setMaxResults(1);

        $this->addPublicDocumentVisibilityFilter($qb, 'd', 'm');

        /** @var ?LsDoc $document */
        $document = $qb->getQuery()->getOneOrNullResult();

        return $document;
    }

    /**
     * @return list<LsItem>
     */
    public function listPublicItems(
        int $limit = 25,
        int $offset = 0,
        ?string $documentIdentifier = null,
        ?string $query = null,
    ): array {
        $limit = $this->normalizeLimit($limit, 25, 100);
        $offset = max(0, $offset);

        $conn = $this->itemRepository->getEntityManager()->getConnection();
        $sql = 'SELECT i.id
                FROM ls_item i
                STRAIGHT_JOIN ls_doc d ON d.id = i.ls_doc_id
                LEFT JOIN mirror_framework m ON m.id = d.mirrored_framework_id
                WHERE (d.adoption_status IS NULL OR d.adoption_status <> :privateDraft)
                  AND (m.visible IS NULL OR m.visible = 1)';
        $params = ['privateDraft' => LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT];
        $types = ['privateDraft' => \PDO::PARAM_STR];

        if (null !== $documentIdentifier && '' !== trim($documentIdentifier)) {
            $sql .= ' AND d.identifier = :documentIdentifier';
            $params['documentIdentifier'] = trim($documentIdentifier);
            $types['documentIdentifier'] = \PDO::PARAM_STR;
        }

        if (null !== $query && '' !== trim($query)) {
            $like = '%'.strtolower(trim($query)).'%';
            $sql .= ' AND (LOWER(i.full_statement) LIKE :q1 OR LOWER(i.abbreviated_statement) LIKE :q2 OR LOWER(i.human_coding_scheme) LIKE :q3 OR LOWER(i.identifier) LIKE :q4)';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $types['q1'] = \PDO::PARAM_STR;
            $types['q2'] = \PDO::PARAM_STR;
            $types['q3'] = \PDO::PARAM_STR;
            $types['q4'] = \PDO::PARAM_STR;
        }

        $sql .= ' ORDER BY i.changed_at DESC, i.id DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        $types['limit'] = \PDO::PARAM_INT;
        $types['offset'] = \PDO::PARAM_INT;

        $ids = $conn->executeQuery($sql, $params, $types)->fetchFirstColumn();

        if ([] === $ids) {
            return [];
        }

        $items = $this->itemRepository->createQueryBuilder('i')
            ->select('i')
            ->where('i.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($items as $item) {
            $byId[$item->getId()] = $item;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function getPublicItemByIdentifier(string $itemIdentifier, ?string $documentIdentifier = null): ?LsItem
    {
        $qb = $this->itemRepository->createQueryBuilder('i')
            ->select('i', 'd')
            ->innerJoin('i.lsDoc', 'd')
            ->leftJoin('d.mirroredFramework', 'm')
            ->andWhere('i.identifier = :itemIdentifier')
            ->andWhere('(d.adoptionStatus IS NULL OR d.adoptionStatus != :privateDraft)')
            ->andWhere('(m.visible IS NULL OR m.visible = 1)')
            ->setParameter('itemIdentifier', trim($itemIdentifier))
            ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT)
            ->orderBy('i.changedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->setMaxResults(1);

        if (null !== $documentIdentifier && '' !== trim($documentIdentifier)) {
            $qb->andWhere('d.identifier = :documentIdentifier')
                ->setParameter('documentIdentifier', trim($documentIdentifier));
        }

        /** @var ?LsItem $item */
        $item = $qb->getQuery()->getOneOrNullResult();

        return $item;
    }

    /**
     * @param list<string>|null $associationTypes
     *
     * @return list<array{
     *     item: LsItem,
     *     relationCount: int,
     *     relations: list<array{
     *         associationId: ?int,
     *         associationIdentifier: string,
     *         type: string,
     *         displayType: string,
     *         direction: 'outgoing'|'incoming',
     *         sequenceNumber: ?int,
     *         notes: ?string
     *     }>
     * }>
     */
    public function findAssociationRelatedItems(
        LsItem $sourceItem,
        int $limit = 20,
        ?array $associationTypes = null,
    ): array {
        $allowedTypes = null;
        if (null !== $associationTypes && [] !== $associationTypes) {
            $allowedTypes = array_fill_keys(array_filter(array_map('trim', $associationTypes), static fn (string $type): bool => '' !== $type), true);
        }

        /** @var array<string, array{
         *     item: LsItem,
         *     relationCount: int,
         *     relations: list<array{
         *         associationId: ?int,
         *         associationIdentifier: string,
         *         type: string,
         *         displayType: string,
         *         direction: 'outgoing'|'incoming',
         *         sequenceNumber: ?int,
         *         notes: ?string
         *     }>
         * }> $related
         */
        $related = [];

        foreach ($sourceItem->getAssociations() as $association) {
            $this->appendAssociationRelation($related, $sourceItem, $association, false, $allowedTypes);
        }

        foreach ($sourceItem->getInverseAssociations() as $association) {
            $this->appendAssociationRelation($related, $sourceItem, $association, true, $allowedTypes);
        }

        $relatedItems = array_values($related);
        usort(
            $relatedItems,
            static function (array $left, array $right): int {
                if ($left['relationCount'] !== $right['relationCount']) {
                    return $right['relationCount'] <=> $left['relationCount'];
                }

                return $right['item']->getChangedAt()->getTimestamp() <=> $left['item']->getChangedAt()->getTimestamp();
            }
        );

        return array_slice($relatedItems, 0, $this->normalizeLimit($limit, 20, 100));
    }

    public function isPublicDocument(LsDoc $document): bool
    {
        if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT === $document->getAdoptionStatus()) {
            return false;
        }

        $mirroredFramework = $document->getMirroredFramework();

        return null === $mirroredFramework || $mirroredFramework->isVisible();
    }

    private function addPublicDocumentVisibilityFilter(QueryBuilder $qb, string $documentAlias, string $mirrorAlias): void
    {
        $qb->leftJoin($documentAlias.'.mirroredFramework', $mirrorAlias)
            ->andWhere('('.$documentAlias.'.adoptionStatus IS NULL OR '.$documentAlias.'.adoptionStatus != :privateDraft)')
            ->andWhere('('.$mirrorAlias.'.visible IS NULL OR '.$mirrorAlias.'.visible = 1)')
            ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);
    }

    /**
     * @param array<string, array{
     *     item: LsItem,
     *     relationCount: int,
     *     relations: list<array{
     *         associationId: ?int,
     *         associationIdentifier: string,
     *         type: string,
     *         displayType: string,
     *         direction: 'outgoing'|'incoming',
     *         sequenceNumber: ?int,
     *         notes: ?string
     *     }>
     * }> $related
     * @param array<string, bool>|null $allowedTypes
     */
    private function appendAssociationRelation(
        array & $related,
        LsItem $sourceItem,
        LsAssociation $association,
        bool $incomingDirection,
        ?array $allowedTypes,
    ): void {
        $associationType = $association->getType();
        if (null === $associationType) {
            return;
        }

        if (null !== $allowedTypes && !isset($allowedTypes[$associationType])) {
            return;
        }

        $relatedItem = $incomingDirection ? $association->getOriginLsItem() : $association->getDestinationLsItem();
        if (null === $relatedItem) {
            return;
        }

        if ($relatedItem->getIdentifier() === $sourceItem->getIdentifier() && $relatedItem->getLsDocIdentifier() === $sourceItem->getLsDocIdentifier()) {
            return;
        }

        if (!$this->isPublicDocument($relatedItem->getLsDoc())) {
            return;
        }

        $key = $relatedItem->getLsDocIdentifier().'::'.$relatedItem->getIdentifier();
        if (!isset($related[$key])) {
            $related[$key] = [
                'item' => $relatedItem,
                'relationCount' => 0,
                'relations' => [],
            ];
        }

        ++$related[$key]['relationCount'];
        $related[$key]['relations'][] = [
            'associationId' => $association->getId(),
            'associationIdentifier' => $association->getIdentifier(),
            'type' => $associationType,
            'displayType' => $association->getDisplayType(),
            'direction' => $incomingDirection ? 'incoming' : 'outgoing',
            'sequenceNumber' => $association->getSequenceNumber(),
            'notes' => $association->getNotes(),
        ];
    }

    private function normalizeLimit(int $limit, int $default, int $max): int
    {
        if ($limit <= 0) {
            return $default;
        }

        return min($limit, $max);
    }
}
