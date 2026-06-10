<?php

declare(strict_types=1);

namespace App\Mcp\OpenSalt;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Util\LikeQueryHelper;
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
        $qb = $this->documentRepository->createQueryBuilder('d')
            ->select('d')
            ->orderBy('d.changedAt', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults($this->normalizeLimit($limit, 25, 100));

        $this->addPublicDocumentVisibilityFilter($qb, 'd', 'm');

        if (null !== $query && '' !== trim($query)) {
            $query = LikeQueryHelper::containsLower(trim($query));
            $qb->andWhere('LOWER(d.title) LIKE :query OR LOWER(d.creator) LIKE :query OR LOWER(d.publisher) LIKE :query OR LOWER(d.identifier) LIKE :query')
                ->setParameter('query', $query);
        }

        /** @var list<LsDoc> $documents */
        $documents = $qb->getQuery()->getResult();

        return $documents;
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
        $qb = $this->itemRepository->createQueryBuilder('i')
            ->select('i', 'd')
            ->innerJoin('i.lsDoc', 'd')
            ->leftJoin('d.mirroredFramework', 'm')
            ->andWhere('(d.adoptionStatus IS NULL OR d.adoptionStatus != :privateDraft)')
            ->andWhere('(m.visible IS NULL OR m.visible = 1)')
            ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT)
            ->orderBy('i.changedAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults($this->normalizeLimit($limit, 25, 100));

        if (null !== $documentIdentifier && '' !== trim($documentIdentifier)) {
            $qb->andWhere('d.identifier = :documentIdentifier')
                ->setParameter('documentIdentifier', trim($documentIdentifier));
        }

        if (null !== $query && '' !== trim($query)) {
            $query = LikeQueryHelper::containsLower(trim($query));
            $qb->andWhere('LOWER(i.fullStatement) LIKE :query OR LOWER(i.abbreviatedStatement) LIKE :query OR LOWER(i.humanCodingScheme) LIKE :query OR LOWER(i.identifier) LIKE :query')
                ->setParameter('query', $query);
        }

        /** @var list<LsItem> $items */
        $items = $qb->getQuery()->getResult();

        return $items;
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
