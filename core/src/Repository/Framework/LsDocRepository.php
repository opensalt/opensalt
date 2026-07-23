<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\DTO\Api\V1\DocumentFilterDto;
use App\DTO\Api\V1\DocumentListResponseDto;
use App\DTO\Api\V1\DocumentPaginationResponseDto;
use App\DTO\Api\V1\PaginationDto;
use App\Entity\Framework\CaseApiInterface;
use App\Entity\Framework\CfRubric;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use App\Security\Permission;
use App\Util\Compare;
use App\Util\LikeQueryHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method LsDoc[]|array findByCreator(string $creator)
 * @method LsDoc|null findOneByIdentifier(string $identifier)
 *
 * @extends ServiceEntityRepository<LsDoc>
 */
class LsDocRepository extends ServiceEntityRepository
{
    final public const MAX_RESULTS = 50_000;

    public function __construct(
        ManagerRegistry $registry,
        private readonly Security $security,
    ) {
        parent::__construct($registry, LsDoc::class);
    }

    /**
     * @return LsDoc[]
     */
    public function findForList(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            $user = null;
        }

        $qb = $this->createQueryBuilder('d')
            ->select('d', 's', 'm')
            ->leftJoin('d.subjects', 's')
            ->leftJoin('d.mirroredFramework', 'm');

        $this->applyUserAccessConditions($qb, $user);

        $qb->orderBy('d.creator', 'ASC')
            ->addOrderBy('d.title', 'ASC')
            ->addOrderBy('d.adoptionStatus', 'ASC');
        $qb->setMaxResults(self::MAX_RESULTS);

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds an object for the API by ['id'=>identifier, 'class'=>class].
     *
     * @param array{'class': class-string, 'id': string} $id
     *
     * @throws NotFoundHttpException
     */
    public function apiFindOneByClassIdentifier(array $id): CaseApiInterface
    {
        /** @var array<array-key, ?CaseApiInterface> $obj */
        $obj = $this->getEntityManager()->getRepository($id['class'])->findBy(['identifier' => $id['id']], null, 1);
        if ([] === $obj) {
            throw new NotFoundHttpException(sprintf('%s object not found.', $id['class']));
        }

        return $obj[array_key_first($obj)];
    }

    public function findOneBySlug(string $slug): ?LsDoc
    {
        if (preg_match('/^\d+$/', $slug)) {
            return $this->find((int) $slug);
        }

        return $this->findOneBy(['urlName' => $slug]);
    }

    public function findAllNonPrivateQueryBuilder(string $alias = 'd'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->where(sprintf('(%s.adoptionStatus != :status OR %s.adoptionStatus IS NULL)', $alias, $alias))
            ->setParameter('status', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT)
        ;
    }

    /**
     * @return LsDoc[]
     */
    public function findAllNonPrivate(?CfDocQuery $query = null): array
    {
        if (null === $query) {
            $query = new CfDocQuery();
        }

        $sortBy = match ($query->getSort()) {
            'updatedAt' => 'd.updatedAt',
            'title' => 'd.title',
            'identifier' => 'd.identifier',
            'lastChangeDateTime' => 'd.changedAt',
            default => 'd.id',
        };

        $qb = $this->findAllNonPrivateQueryBuilder('d');
        $qb->setFirstResult($query->getOffset())
            ->setMaxResults($query->getLimit())
            ->addOrderBy($sortBy, $query->getOrderBy())
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return LsDoc[]
     */
    public function findNonPrivateByCreator(string $creator): array
    {
        $qb = $this->findAllNonPrivateQueryBuilder()
            ->andWhere('d.creator = :creator')
            ->setParameter('creator', $creator)
            ->setMaxResults(self::MAX_RESULTS);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get a list of all items for an LsDoc.
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findAllChildrenArray(LsDoc $lsDoc): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, t, a, g, adi, add
            FROM '.LsItem::class.' i INDEX BY i.id
            LEFT JOIN i.itemType t
            LEFT JOIN i.associations a WITH a.lsDoc = :lsDocId AND a.type = :childOfType
            LEFT JOIN a.group g
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE i.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);
        $query->setMaxResults(self::MAX_RESULTS);

        /** @var array $results */
        $results = $query->getResult(Query::HYDRATE_ARRAY);

        foreach ($results as $key => $result) {
            $results[$key]['children'] = [];
        }

        foreach ($results as $key => $result) {
            $this->processItemAssociations($results, $key, $result);
        }

        foreach ($results as $key => $result) {
            if (!empty($results[$key]['children'])) {
                $this->rankItems($results[$key]['children']);
            }
        }

        return $results;
    }

    private function processItemAssociations(array &$results, int $key, array $result): void
    {
        foreach ($result['associations'] as $association) {
            if (!empty($association['destinationLsItem'])) {
                $parent = $association['destinationLsItem'];
                $results[$parent['id']]['children'][] = $result;
                $results[$key]['assoc'][$parent['id']] = $this->buildAssocEntry($association);
            } elseif (!empty($association['destinationLsDoc'])) {
                $results[$key]['assoc']['doc'] = $this->buildAssocEntry($association);
            }
        }
    }

    private function buildAssocEntry(array $association): array
    {
        return [
            'id' => $association['id'],
            'sequenceNumber' => $association['sequenceNumber'],
            'group' => !empty($association['group']) ? $association['group']['id'] : '',
        ];
    }

    /**
     * Rank the items in $itemArray
     *   - by "rank"
     *   - then by "listEnumInSource"
     *   - then by "humanCodingScheme".
     */
    private function rankItems(array &$itemArray): void
    {
        Compare::sortArrayByFields($itemArray, ['sequenceNumber', 'listEnumInSource', 'humanCodingScheme']);
    }

    /**
     * Get a list of ids for all items that have parents for an LsDoc.
     *
     * @return array array of LsItem ids
     */
    public function findAllItemsWithParentsArray(LsDoc $lsDoc): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i.id
            FROM '.LsItem::class.' i INDEX by i.id
            JOIN i.associations a WITH a.lsDoc = :lsDocId AND a.type = :childOfType
            LEFT JOIN a.destinationLsItem p WITH p.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc pd WITH pd.id = :lsDocId
            WHERE i.lsDoc = :lsDocId
              AND (p.lsDoc IS NOT NULL OR pd.id IS NOT NULL)
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);
        $query->setMaxResults(self::MAX_RESULTS);

        return $query->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Get a list of all items for an LsDoc.
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findTopChildrenIds(LsDoc $lsDoc): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, a, add
            FROM '.LsItem::class.' i INDEX BY i.id
            JOIN i.associations a WITH a.lsDoc = :lsDocId AND a.type = :childOfType
            JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE i.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);
        $query->setMaxResults(self::MAX_RESULTS);

        $results = $query->getResult(Query::HYDRATE_ARRAY);

        $this->rankItems($results);

        return array_keys($results);
    }

    /**
     * Build a hierarchical tree for a document, including cross-framework items.
     *
     * Returns an array with:
     * - 'tree': array of root-level tree nodes (each with nested 'children')
     * - 'definitions': metadata about association groups, item types, subjects, etc.
     *
     * Each tree node contains:
     * - identifier, uri, documentIdentifier, humanCodingScheme, fullStatement,
     *   abbreviatedStatement, listEnumeration, itemType, sequenceNumber,
     *   lastChangeDateTime, childOfAssociationIdentifier, associationGroupIdentifier,
     *   isCrossFramework, isUnresolved, children
     */
    public function findTreeForDocument(LsDoc $lsDoc, bool $lightweight = false): array
    {
        $em = $this->getEntityManager();
        $viewedDocId = $lsDoc->getId();
        $viewedDocIdentifier = $lsDoc->getIdentifier();

        $childOfAssocs = $this->fetchChildOfAssociations($em, $viewedDocId);
        $docChildAssocs = $this->fetchDocChildAssociations($em, $viewedDocId);

        $itemDataMap = $this->fetchAllItemsMap($em, $viewedDocId);

        $itemLicenceMap = [];
        $itemSubjectMap = [];
        if ([] !== $itemDataMap && !$lightweight) {
            $itemLicenceMap = $this->fetchItemLicenceMap($em, $viewedDocId);
            $itemSubjectMap = $this->fetchItemSubjectMap($em, $viewedDocId);
        }

        $maps = $this->buildParentAndAssocMaps($childOfAssocs, $docChildAssocs, $viewedDocId, $viewedDocIdentifier);
        $parentMap = $maps['parentMap'];
        $assocMap = $maps['assocMap'];
        $childIds = $maps['childIds'];

        $foreignItems = $this->fetchForeignItems($em, $maps['foreignItemIdentifiers']);

        $itemTypeRepo = $em->getRepository(LsDefItemType::class);
        $itemTypeCache = [];

        $buildNode = $this->createBuildNodeClosure(
            $itemDataMap, $foreignItems, $assocMap,
            $viewedDocId, $viewedDocIdentifier, $lightweight,
            $itemTypeRepo, $itemTypeCache, $em,
            $itemLicenceMap, $itemSubjectMap
        );

        $childrenMap = $this->buildChildrenMap($parentMap, $assocMap);

        $tree = $this->buildTreeRecursive($viewedDocIdentifier, $buildNode, $childrenMap, $itemDataMap, $foreignItems, $viewedDocId);

        $tree = $this->appendOrphanItems($tree, $itemDataMap, $childIds, $viewedDocIdentifier, $lightweight, $itemTypeRepo, $itemTypeCache);

        $definitions = $lightweight ? [] : $this->buildTreeDefinitions($lsDoc);

        return [
            'tree' => $tree,
            'definitions' => $definitions,
        ];
    }

    private function fetchChildOfAssociations(EntityManagerInterface $em, int $viewedDocId): array
    {
        $query = $em->createQuery('
            SELECT a.identifier as assocIdentifier, a.sequenceNumber,
                   g.identifier as groupIdentifier,
                   oi.identifier as originIdentifier, oi.uri as originUri,
                   oi.humanCodingScheme as originHcs, oi.fullStatement as originFs,
                   oi.abbreviatedStatement as originAbs, oi.listEnumInSource as originLe,
                   IDENTITY(oi.lsDoc) as originDocId,
                   IDENTITY(oi.itemType) as originItemTypeId,
                   oi.changedAt as originChangedAt,
                   a.destinationNodeIdentifier as destNodeIdentifier,
                   di.identifier as destIdentifier, di.uri as destUri,
                   IDENTITY(di.lsDoc) as destDocId,
                   a.extra as assocExtra,
                   a.extensions as assocExtensions
            FROM '.LsAssociation::class.' a
            LEFT JOIN a.group g
            JOIN a.originLsItem oi
            LEFT JOIN a.destinationLsItem di
            WHERE a.lsDoc = :viewedDocId
              AND a.type = :childOfType
        ');
        $query->setParameter('viewedDocId', $viewedDocId);
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);

        return $query->getResult(Query::HYDRATE_ARRAY);
    }

    private function fetchDocChildAssociations(EntityManagerInterface $em, int $viewedDocId): array
    {
        $query = $em->createQuery('
            SELECT a.identifier as assocIdentifier, a.sequenceNumber,
                   g.identifier as groupIdentifier,
                   oi.identifier as originIdentifier, oi.uri as originUri,
                   oi.humanCodingScheme as originHcs, oi.fullStatement as originFs,
                   oi.abbreviatedStatement as originAbs, oi.listEnumInSource as originLe,
                   IDENTITY(oi.lsDoc) as originDocId,
                   IDENTITY(oi.itemType) as originItemTypeId,
                   oi.changedAt as originChangedAt,
                   a.destinationNodeIdentifier as destNodeIdentifier,
                   a.extra as assocExtra,
                   a.extensions as assocExtensions
            FROM '.LsAssociation::class.' a
            LEFT JOIN a.group g
            JOIN a.originLsItem oi
            JOIN a.destinationLsDoc dd WITH dd.id = :viewedDocId
            WHERE a.lsDoc = :viewedDocId
              AND a.type = :childOfType
        ');
        $query->setParameter('viewedDocId', $viewedDocId);
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);

        return $query->getResult(Query::HYDRATE_ARRAY);
    }

    private function fetchAllItemsMap(EntityManagerInterface $em, int $viewedDocId): array
    {
        $allItemsQuery = $em->createQuery('
            SELECT i.identifier, i.uri, i.humanCodingScheme, i.fullStatement,
                   i.abbreviatedStatement, i.listEnumInSource, i.changedAt,
                   i.discriminator, i.extensions, i.extra,
                   i.conceptKeywords, i.language, i.educationalAlignment,
                   i.itemTypeText,
                   IDENTITY(i.lsDoc) as lsDoc,
                   IDENTITY(i.itemType) as itemType
            FROM '.LsItem::class.' i
            WHERE i.lsDoc = :docId
        ');
        $allItemsQuery->setParameter('docId', $viewedDocId);
        $allItems = $allItemsQuery->getResult(Query::HYDRATE_ARRAY);

        $itemDataMap = [];
        foreach ($allItems as $item) {
            $itemDataMap[$item['identifier']] = $item;
        }

        return $itemDataMap;
    }

    private function fetchItemLicenceMap(EntityManagerInterface $em, int $viewedDocId): array
    {
        $licenceQuery = $em->createQuery('
            SELECT i.identifier as itemIdentifier, l.identifier as licenceIdentifier,
                   l.uri as licenceUri, l.title as licenceTitle
            FROM '.LsItem::class.' i
            JOIN i.licence l
            WHERE i.lsDoc = :docId
        ');
        $licenceQuery->setParameter('docId', $viewedDocId);
        $licenceResults = $licenceQuery->getResult(Query::HYDRATE_ARRAY);

        $itemLicenceMap = [];
        foreach ($licenceResults as $lr) {
            $itemLicenceMap[$lr['itemIdentifier']] = [
                'identifier' => $lr['licenceIdentifier'],
                'uri' => $lr['licenceUri'],
                'title' => $lr['licenceTitle'],
            ];
        }

        return $itemLicenceMap;
    }

    private function fetchItemSubjectMap(EntityManagerInterface $em, int $viewedDocId): array
    {
        $subjectQuery = $em->createQuery('
            SELECT i.identifier as itemIdentifier, s.identifier as subjectIdentifier,
                   s.uri as subjectUri, s.title as subjectTitle
            FROM '.LsItem::class.' i
            JOIN i.subjects s
            WHERE i.lsDoc = :docId
        ');
        $subjectQuery->setParameter('docId', $viewedDocId);
        $subjectResults = $subjectQuery->getResult(Query::HYDRATE_ARRAY);

        $itemSubjectMap = [];
        foreach ($subjectResults as $sr) {
            $itemSubjectMap[$sr['itemIdentifier']][] = [
                'identifier' => $sr['subjectIdentifier'],
                'uri' => $sr['subjectUri'],
                'title' => $sr['subjectTitle'],
            ];
        }

        return $itemSubjectMap;
    }

    private function buildParentAndAssocMaps(array $childOfAssocs, array $docChildAssocs, int $viewedDocId, string $viewedDocIdentifier): array
    {
        $parentMap = [];
        $assocMap = [];
        $childIds = [];
        $foreignItemIdentifiers = [];

        foreach ($childOfAssocs as $row) {
            $originId = $row['originIdentifier'];
            $destId = $row['destIdentifier'] ?? $row['destNodeIdentifier'];

            $parentMap[$originId] = $destId;
            $assocMap[$originId] = $this->extractAssocData($row);
            $childIds[$originId] = true;

            if (null !== $destId && null !== $row['destDocId'] && (int) $row['destDocId'] !== $viewedDocId) {
                $foreignItemIdentifiers[$destId] = true;
            }
            if (null !== $row['originDocId'] && (int) $row['originDocId'] !== $viewedDocId) {
                $foreignItemIdentifiers[$originId] = true;
            }
        }

        foreach ($docChildAssocs as $row) {
            $originId = $row['originIdentifier'];

            $parentMap[$originId] = $viewedDocIdentifier;
            $assocMap[$originId] = $this->extractAssocData($row);
            $childIds[$originId] = true;

            if (null !== $row['originDocId'] && (int) $row['originDocId'] !== $viewedDocId) {
                $foreignItemIdentifiers[$originId] = true;
            }
        }

        return [
            'parentMap' => $parentMap,
            'assocMap' => $assocMap,
            'childIds' => $childIds,
            'foreignItemIdentifiers' => $foreignItemIdentifiers,
        ];
    }

    private function extractAssocData(array $row): array
    {
        return [
            'assocIdentifier' => $row['assocIdentifier'],
            'sequenceNumber' => $row['sequenceNumber'],
            'groupIdentifier' => $row['groupIdentifier'],
            'originFs' => $row['originFs'],
            'originHcs' => $row['originHcs'],
            'originAbs' => $row['originAbs'],
            'assocExtra' => $row['assocExtra'],
            'extensions' => $row['assocExtensions'] ?? [],
        ];
    }

    private function fetchForeignItems(EntityManagerInterface $em, array $foreignItemIdentifiers): array
    {
        if ([] === $foreignItemIdentifiers) {
            return [];
        }

        $foreignQuery = $em->createQuery('
            SELECT i.identifier, i.uri, i.humanCodingScheme, i.fullStatement,
                   i.abbreviatedStatement, i.listEnumInSource, i.changedAt,
                   i.discriminator, i.extensions, i.extra,
                   i.itemTypeText,
                   IDENTITY(i.lsDoc) as lsDoc,
                   IDENTITY(i.itemType) as itemType
            FROM '.LsItem::class.' i
            WHERE i.identifier IN (:ids)
        ');
        $foreignQuery->setParameter('ids', array_keys($foreignItemIdentifiers));
        $foreignResult = $foreignQuery->getResult(Query::HYDRATE_ARRAY);

        $foreignItems = [];
        foreach ($foreignResult as $fi) {
            $foreignItems[$fi['identifier']] = $fi;
        }

        return $foreignItems;
    }

    /**
     * @param EntityRepository<LsDefItemType> $itemTypeRepo
     */
    private function createBuildNodeClosure(
        array $itemDataMap,
        array $foreignItems,
        array $assocMap,
        int $viewedDocId,
        string $viewedDocIdentifier,
        bool $lightweight,
        EntityRepository $itemTypeRepo,
        array & $itemTypeCache,
        EntityManagerInterface $em,
        array $itemLicenceMap,
        array $itemSubjectMap,
    ): \Closure {
        return function (string $identifier, bool $isCrossFramework = false) use (
            &$itemTypeCache, $itemDataMap, $foreignItems, $assocMap,
            $viewedDocId, $viewedDocIdentifier, $lightweight,
            $itemTypeRepo, $em,
            $itemLicenceMap, $itemSubjectMap
        ): ?array {
            static $visited = [];
            if (isset($visited[$identifier])) {
                return null;
            }
            $visited[$identifier] = true;

            $isForeign = $isCrossFramework;
            $docId = $viewedDocIdentifier;

            if (isset($itemDataMap[$identifier])) {
                $item = $itemDataMap[$identifier];
                $itemDocId = $item['lsDoc'] ?? null;
                if (null !== $itemDocId && (int) $itemDocId !== $viewedDocId) {
                    $isForeign = true;
                }
            } elseif (isset($foreignItems[$identifier])) {
                $item = $foreignItems[$identifier];
                $isForeign = true;
            } else {
                return $this->buildUnresolvedNode($identifier, $assocMap[$identifier] ?? [], $lightweight);
            }

            $itemDoc = $item['lsDoc'] ?? null;
            $docInfo = $this->resolveDocInfo($itemDoc, $viewedDocId, $em);
            $docId = $docInfo['docId'] ?? $viewedDocIdentifier;
            $docTitle = $docInfo['docTitle'];

            if ($lightweight) {
                return $this->buildLightweightNode($identifier, $item, $docId, $docTitle, $isForeign, $assocMap);
            }

            return $this->buildFullNode(
                $identifier, $item, $docId, $docTitle, $isForeign,
                $assocMap, $itemTypeRepo, $itemTypeCache,
                $itemLicenceMap, $itemSubjectMap
            );
        };
    }

    private function resolveDocInfo(mixed $itemDoc, int $viewedDocId, EntityManagerInterface $em): array
    {
        if (null === $itemDoc || (int) $itemDoc === $viewedDocId) {
            return ['docId' => null, 'docTitle' => null];
        }
        $docEntity = $em->getRepository(LsDoc::class)->find($itemDoc);
        if (null === $docEntity) {
            return ['docId' => null, 'docTitle' => null];
        }

        return ['docId' => $docEntity->getIdentifier(), 'docTitle' => $docEntity->getTitle()];
    }

    private function buildUnresolvedNode(string $identifier, array $assoc, bool $lightweight): array
    {
        $node = [
            'identifier' => $identifier,
            'uri' => null,
            'documentIdentifier' => null,
            'humanCodingScheme' => $assoc['originHcs'] ?? null,
            'fullStatement' => $assoc['originFs'] ?? null,
            'isCrossFramework' => true,
            'isUnresolved' => true,
            'children' => [],
        ];
        if (!$lightweight) {
            $node['abbreviatedStatement'] = $assoc['originAbs'] ?? null;
            $node['listEnumeration'] = null;
            $node['itemType'] = null;
            $node['sequenceNumber'] = null;
            $node['lastChangeDateTime'] = null;
            $node['childOfAssociationIdentifier'] = $assoc['assocIdentifier'] ?? null;
            $node['associationGroupIdentifier'] = $assoc['groupIdentifier'] ?? null;
            $node['discriminator'] = 0;
            $node['extensions'] = [];
            $node['additionalFields'] = [];
            $node['associationAdditionalFields'] = $assoc['assocExtra']['customFields'] ?? [];
        } else {
            $node['discriminator'] = 0;
            $node['extensions'] = [];
            $node['additionalFields'] = [];
            $node['listEnumeration'] = null;
            $node['sequenceNumber'] = null;
        }

        return $node;
    }

    private function buildLightweightNode(string $identifier, array $item, string $docId, ?string $docTitle, bool $isForeign, array $assocMap = []): array
    {
        return [
            'identifier' => $identifier,
            'documentIdentifier' => $docId,
            'documentTitle' => $docTitle,
            'humanCodingScheme' => $item['humanCodingScheme'] ?? null,
            'fullStatement' => $item['fullStatement'] ?? null,
            'abbreviatedStatement' => $item['abbreviatedStatement'] ?? null,
            'listEnumeration' => $item['listEnumInSource'] ?? null,
            'sequenceNumber' => $assocMap[$identifier]['sequenceNumber'] ?? null,
            'isCrossFramework' => $isForeign,
            'discriminator' => $item['discriminator'] ?? 0,
            'extensions' => $item['extensions'] ?? [],
            'additionalFields' => $item['extra']['customFields'] ?? [],
            'children' => [],
        ];
    }

    /**
     * @param EntityRepository<LsDefItemType> $itemTypeRepo
     */
    private function buildFullNode(
        string $identifier,
        array $item,
        string $docId,
        ?string $docTitle,
        bool $isForeign,
        array $assocMap,
        EntityRepository $itemTypeRepo,
        array & $itemTypeCache,
        array $itemLicenceMap,
        array $itemSubjectMap,
    ): array {
        $itemTypeName = null;
        if (isset($item['itemType'])) {
            $itId = $item['itemType'];
            if (!isset($itemTypeCache[$itId])) {
                $itEntity = $itemTypeRepo->find($itId);
                $itemTypeCache[$itId] = $itEntity?->getTitle();
            }
            $itemTypeName = $itemTypeCache[$itId];
        }
        if (null === $itemTypeName && !empty($item['itemTypeText'])) {
            $itemTypeName = $item['itemTypeText'];
        }

        return [
            'identifier' => $identifier,
            'uri' => $item['uri'] ?? null,
            'documentIdentifier' => $docId,
            'documentTitle' => $docTitle,
            'humanCodingScheme' => $item['humanCodingScheme'] ?? null,
            'fullStatement' => $item['fullStatement'] ?? null,
            'abbreviatedStatement' => $item['abbreviatedStatement'] ?? null,
            'listEnumeration' => $item['listEnumInSource'] ?? null,
            'itemType' => $itemTypeName,
            'sequenceNumber' => $assocMap[$identifier]['sequenceNumber'] ?? null,
            'lastChangeDateTime' => ($item['changedAt'] ?? null) instanceof \DateTimeInterface
                ? $item['changedAt']->format('c')
                : $item['changedAt'],
            'childOfAssociationIdentifier' => $assocMap[$identifier]['assocIdentifier'] ?? null,
            'associationGroupIdentifier' => $assocMap[$identifier]['groupIdentifier'] ?? null,
            'isCrossFramework' => $isForeign,
            'isUnresolved' => false,
            'discriminator' => $item['discriminator'] ?? 0,
            'extensions' => $item['extensions'] ?? [],
            'additionalFields' => $item['extra']['customFields'] ?? [],
            'associationAdditionalFields' => $assocMap[$identifier]['assocExtra']['customFields'] ?? [],
            'licenseURI' => $itemLicenceMap[$identifier] ?? null,
            'subjectURI' => $itemSubjectMap[$identifier] ?? [],
            'conceptKeywords' => $item['conceptKeywords'] ?? null,
            'language' => $item['language'] ?? null,
            'educationLevel' => $item['educationalAlignment'] ?? null,
            'children' => [],
        ];
    }

    private function buildChildrenMap(array $parentMap, array $assocMap): array
    {
        $childrenMap = [];
        foreach ($parentMap as $childId => $parentId) {
            if (!isset($childrenMap[$parentId])) {
                $childrenMap[$parentId] = [];
            }
            $seq = $assocMap[$childId]['sequenceNumber'] ?? null;
            $childrenMap[$parentId][] = ['id' => $childId, 'seq' => $seq];
        }

        foreach ($childrenMap as &$children) {
            usort($children, $this->compareChildSequence(...));
        }
        unset($children);

        return $childrenMap;
    }

    private function compareChildSequence(array $a, array $b): int
    {
        if (null !== $a['seq'] && null !== $b['seq']) {
            return (int) $a['seq'] <=> (int) $b['seq'];
        }
        if (null !== $a['seq']) {
            return -1;
        }
        if (null !== $b['seq']) {
            return 1;
        }

        return 0;
    }

    private function buildTreeRecursive(string $parentId, \Closure $buildNode, array $childrenMap, array $itemDataMap, array $foreignItems, int $viewedDocId): array
    {
        $result = [];
        if (!isset($childrenMap[$parentId])) {
            return $result;
        }

        foreach ($childrenMap[$parentId] as $childInfo) {
            $childId = $childInfo['id'];
            $isForeign = $this->isItemForeign($childId, $itemDataMap, $foreignItems, $viewedDocId);

            $node = $buildNode($childId, $isForeign);
            if (null === $node) {
                continue;
            }

            $node['children'] = $this->buildTreeRecursive($childId, $buildNode, $childrenMap, $itemDataMap, $foreignItems, $viewedDocId);
            $result[] = $node;
        }

        return $result;
    }

    private function isItemForeign(string $childId, array $itemDataMap, array $foreignItems, int $viewedDocId): bool
    {
        if (isset($itemDataMap[$childId])) {
            $itemDocId = $itemDataMap[$childId]['lsDoc'] ?? null;

            return null !== $itemDocId && (int) $itemDocId !== $viewedDocId;
        }

        return isset($foreignItems[$childId]);
    }

    /**
     * @param EntityRepository<LsDefItemType> $itemTypeRepo
     */
    private function appendOrphanItems(
        array $tree,
        array $itemDataMap,
        array $childIds,
        string $viewedDocIdentifier,
        bool $lightweight,
        EntityRepository $itemTypeRepo,
        array & $itemTypeCache,
    ): array {
        foreach ($itemDataMap as $identifier => $item) {
            if (isset($childIds[$identifier])) {
                continue;
            }

            if ($lightweight) {
                $tree[] = $this->buildLightweightNode($identifier, $item, $viewedDocIdentifier, null, false);
            } else {
                $assocMap = [];
                $tree[] = $this->buildFullNode(
                    $identifier, $item, $viewedDocIdentifier, null, false,
                    $assocMap, $itemTypeRepo, $itemTypeCache,
                    [], []
                );
            }
        }

        return $tree;
    }

    private function buildTreeDefinitions(LsDoc $lsDoc): array
    {
        $definitions = [];

        $groupings = $this->findAllDocAssociationGroups($lsDoc, Query::HYDRATE_ARRAY);
        $definitions['CFAssociationGroupings'] = array_values(array_map(static function (array $g): array {
            return [
                'identifier' => $g['identifier'] ?? null,
                'uri' => $g['uri'] ?? null,
                'title' => $g['title'] ?? null,
                'description' => $g['description'] ?? null,
            ];
        }, $groupings));

        $itemTypes = $this->findAllUsedItemTypes($lsDoc, Query::HYDRATE_ARRAY);
        $definitions['CFItemTypes'] = array_values(array_map(static function (array $t): array {
            return [
                'identifier' => $t['identifier'] ?? null,
                'uri' => $t['uri'] ?? null,
                'title' => $t['title'] ?? null,
                'description' => $t['description'] ?? null,
            ];
        }, $itemTypes));

        $concepts = $this->findAllUsedConcepts($lsDoc, Query::HYDRATE_ARRAY);
        $definitions['CFConcepts'] = array_values(array_map(static function (array $c): array {
            return [
                'identifier' => $c['identifier'] ?? null,
                'uri' => $c['uri'] ?? null,
                'title' => $c['title'] ?? null,
                'keywords' => $c['keywords'] ?? null,
            ];
        }, $concepts));

        $subjects = $lsDoc->getSubjects();
        $definitions['CFSubjects'] = array_values(array_map(static function (LsDefSubject $s): array {
            return [
                'identifier' => $s->getIdentifier(),
                'uri' => $s->getUri(),
                'title' => $s->getTitle(),
            ];
        }, $subjects->toArray()));

        $licences = $this->findAllUsedLicences($lsDoc, Query::HYDRATE_ARRAY);
        $definitions['CFLicenses'] = array_values(array_map(static function (array $l): array {
            return [
                'identifier' => $l['identifier'] ?? null,
                'uri' => $l['uri'] ?? null,
                'title' => $l['title'] ?? null,
                'description' => $l['description'] ?? null,
                'licenseText' => $l['licenseText'] ?? null,
            ];
        }, $licences));

        return $definitions;
    }

    /**
     * Delete an LsDoc and all associated items and associations.
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteDocument(LsDoc $lsDoc, ?\Closure $progressCallback = null): void
    {
        $conn = $this->getEntityManager()->getConnection();

        if (null === $progressCallback) {
            $progressCallback = static function (string $message = ''): void {
            };
        }

        $docId = $lsDoc->getId();

        $steps = [
            ['Deleting object locks', 'DELETE FROM salt_object_lock WHERE doc_id = :lsDocId'],
            ['Deleting associations', 'DELETE FROM ls_association WHERE ls_doc_id = :lsDocId'],
            ['Deleting origin associations', 'DELETE FROM ls_association WHERE origin_lsitem_id IN (SELECT i.id FROM ls_item i WHERE i.ls_doc_id = :lsDocId)'],
            ['Deleting destination associations', 'DELETE FROM ls_association WHERE destination_lsitem_id IN (SELECT i.id FROM ls_item i WHERE i.ls_doc_id = :lsDocId)'],
            ['Deleting association groups', 'DELETE FROM ls_def_association_grouping WHERE ls_doc_id = :lsDocId'],
            ['Deleting rubric references to items', 'UPDATE rubric_criterion SET ls_item_id = NULL WHERE ls_item_id IN (SELECT id FROM ls_item WHERE ls_doc_id = :lsDocId)'],
            ['Deleting item subject links', 'DELETE FROM ls_item_subject WHERE ls_item_id IN (SELECT id FROM ls_item WHERE ls_doc_id = :lsDocId)'],
            ['Deleting item concept links', 'DELETE FROM ls_item_concept WHERE ls_item_id IN (SELECT id FROM ls_item WHERE ls_doc_id = :lsDocId)'],
            ['Deleting items', 'DELETE FROM ls_item WHERE ls_doc_id = :lsDocId'],
            ['Deleting document subjects', 'DELETE FROM ls_doc_subject WHERE ls_doc_id = :lsDocId'],
            ['Deleting document import logs', 'DELETE FROM import_logs WHERE ls_doc_id = :lsDocId'],
            ['Deleting acls', 'DELETE FROM salt_user_doc_acl WHERE doc_id = :lsDocId'],
            ['Deleting document attributes', 'DELETE FROM ls_doc_attribute WHERE ls_doc_id = :lsDocId'],
            ['Deleting document', 'DELETE FROM ls_doc WHERE id = :lsDocId'],
        ];

        $conn->beginTransaction();
        try {
            foreach ($steps as [$message, $sql]) {
                $progressCallback($message);
                $stmt = $conn->prepare($sql);
                $stmt->bindValue('lsDocId', $docId);
                $stmt->executeStatement();
            }
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        $progressCallback('Done');
    }

    public function copyDocumentContentToDoc(LsDoc $fromDoc, LsDoc $toDoc, bool $exactMatchAssocs = false): void
    {
        foreach ($fromDoc->getTopLsItems() as $oldItem) {
            $newItem = $oldItem->copyToLsDoc($toDoc, null, $exactMatchAssocs);
            $toDoc->addTopLsItem($newItem);
        }
    }

    public function makeDerivative(LsDoc $oldLsDoc, ?LsDoc $newLsDoc = null): LsDoc
    {
        $em = $this->getEntityManager();
        if (null === $newLsDoc) {
            $newLsDoc = $this->createDerivedDoc($oldLsDoc);
        }

        foreach ($oldLsDoc->getAssociationGroupings() as $assocGroup) {
            $assocGroup->duplicateToLsDoc($newLsDoc);
        }

        $em->persist($newLsDoc);

        return $newLsDoc;
    }

    private function createDerivedDoc(LsDoc $source): LsDoc
    {
        $new = new LsDoc();
        $new->setTitle($source->getTitle().' - Derived');
        $new->setCreator($source->getCreator());
        $new->setVersion($source->getVersion());
        $new->setDescription($source->getDescription());
        $new->setSubject($source->getSubject());
        $new->setNote($source->getNote());
        $new->setLanguage($source->getLanguage());
        $new->setOrg($source->getOrg());
        $new->setUser($source->getUser());
        $new->setLicence($source->getLicence());

        return $new;
    }

    public function copyDocumentToItem(LsDoc $fromDoc, LsDoc $toDoc, ?\Closure $progressCallback = null): void
    {
        $em = $this->getEntityManager();

        if (null === $progressCallback) {
            $progressCallback = static function ($message = ''): void {
            };
        }

        $progressCallback('Adding framework as an item in another framework');

        $item = $toDoc->createItem();
        $item->setFullStatement($fromDoc->getTitle());
        $item->setNotes($fromDoc->getNote());

        $toDoc->addTopLsItem($item);
        $em->persist($item);

        $this->copyDocAssociations($fromDoc, $toDoc, $item, $em);

        foreach ($fromDoc->getTopLsItems() as $oldItem) {
            $newItem = $oldItem->duplicateToLsDoc($toDoc);
            $item->addChild($newItem);
        }

        $progressCallback('Done');
    }

    private function copyDocAssociations(LsDoc $fromDoc, LsDoc $toDoc, LsItem $item, EntityManagerInterface $em): void
    {
        foreach ($fromDoc->getAssociations() as $oldAssoc) {
            $newAssoc = $toDoc->createAssociation();
            $newAssoc->setOriginLsItem($item);
            $newAssoc->setType($oldAssoc->getType());
            $newAssoc->setDestination($oldAssoc->getDestination(), $oldAssoc->getDestinationNodeIdentifier());
            $item->addAssociation($newAssoc);
            $em->persist($newAssoc);
        }
    }

    /**
     * Get an array representing the entire CF package.
     */
    public function getPackageArray(LsDoc $doc): array
    {
        $pkg = [
            'CFDocument' => $doc,
            'CFItems' => array_values($this->findAllItems($doc, Query::HYDRATE_OBJECT)),
            'CFAssociations' => array_values($this->findAllAssociations($doc, Query::HYDRATE_OBJECT)),
            'CFDefinitions' => [
                'CFConcepts' => $this->findAllUsedConcepts($doc, Query::HYDRATE_OBJECT),
                'CFSubjects' => $doc->getSubjects(),
                'CFLicenses' => array_values($this->findAllUsedLicences($doc, Query::HYDRATE_OBJECT)),
                'CFItemTypes' => $this->findAllUsedItemTypes($doc, Query::HYDRATE_OBJECT),
                'CFAssociationGroupings' => $this->findAllUsedAssociationGroups($doc, Query::HYDRATE_OBJECT),
            ],
        ];

        $rubrics = $this->findAllUsedRubrics($doc, Query::HYDRATE_OBJECT);
        if ([] !== $rubrics) {
            $pkg['CFRubrics'] = $rubrics;
        }

        return $pkg;
    }

    /**
     * Get a list of all items for an LsDoc.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findAllItems(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY, int $start = 0, int $limit = 0): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, t, a, adi, add, c
            FROM '.LsItem::class.' i INDEX BY i.id
            LEFT JOIN i.itemType t
            LEFT JOIN i.concepts c
            LEFT JOIN i.associations a WITH a.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE i.lsDoc = :lsDocId
              AND i.id > :start
            ORDER BY i.id
        ');
        if ($limit > 0) {
            $query->setMaxResults(min($limit, self::MAX_RESULTS));
        } else {
            $query->setMaxResults(self::MAX_RESULTS);
        }
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('start', $start);

        return $query->getResult($format);
    }

    /**
     * Get a list of all items for an LsDoc.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findAllItemsForCFPackage(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY, int $start = 0, int $limit = 0): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, t, c
            FROM '.LsItem::class.' i INDEX BY i.id
            LEFT JOIN i.itemType t
            LEFT JOIN i.concepts c
            WHERE i.lsDoc = :lsDocId
              AND i.id > :start
            ORDER BY i.id
        ');
        if ($limit > 0) {
            $query->setMaxResults(min($limit, self::MAX_RESULTS));
        } else {
            $query->setMaxResults(self::MAX_RESULTS);
        }
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('start', $start);

        return $query->getResult($format);
    }

    /**
     * Get a list of all item types used in a document.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedItemTypes(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT t
            FROM '.LsDefItemType::class.' t, '.LsItem::class.' i
            WHERE i.lsDoc = :lsDocId
              AND i.itemType = t
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        return $query->getResult($format);
    }

    /**
     * Get a list of all associations for an LsDoc.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllAssociations(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT a, ag, adi, aoi, add
            FROM '.LsAssociation::class.' a INDEX BY a.id
            LEFT JOIN a.group ag
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.originLsItem aoi WITH aoi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE a.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setMaxResults(self::MAX_RESULTS);

        return $query->getResult($format);
    }

    /**
     * Get a list of all associations for an LsDoc.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return iterable<array>|iterable<LsAssociation>
     */
    public function findAllAssociationsIterator(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY, int $start = 0, int $limit = 0): iterable
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT a, ag, adi, aoi, add
            FROM '.LsAssociation::class.' a INDEX BY a.id
            LEFT JOIN a.group ag
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.originLsItem aoi WITH aoi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE a.lsDoc = :lsDocId
              AND a.id > :start
            ORDER BY a.id
        ');
        if ($limit > 0) {
            $query->setMaxResults(min($limit, self::MAX_RESULTS));
        } else {
            $query->setMaxResults(self::MAX_RESULTS);
        }
        $query->setParameter('start', $start);
        $query->setParameter('lsDocId', $lsDoc->getId());

        return $query->toIterable([], $format);
    }

    /**
     * Get a list of all association groups used in an LsDoc.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllUsedAssociationGroups(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT ag
            FROM '.LsDefAssociationGrouping::class.' ag, '.LsAssociation::class.' a
            WHERE a.lsDoc = :lsDocId
              AND a.group = ag
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        return $query->getResult($format);
    }

    /**
     * Get a list of all concepts used in a document.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedConcepts(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT c
            FROM '.LsDefConcept::class.' c, '.LsItem::class.' i
            WHERE i.lsDoc = :lsDocId
              AND c MEMBER OF i.concepts
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        return $query->getResult($format);
    }

    /**
     * Get a list of all licences used in a document.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedLicences(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT l
            FROM '.LsDefLicence::class.' l INDEX BY l.id, '.LsItem::class.' i
            WHERE (i.lsDoc = :lsDocId AND i.licence = l)
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $results = $query->getResult($format);

        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT l
            FROM '.LsDefLicence::class.' l INDEX BY l.id, '.LsDoc::class.' d
            WHERE (d.id = :lsDocId AND d.licence = l)
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $docResults = $query->getResult($format);

        foreach ($docResults as $id => $result) {
            $results[$id] = $result;
        }

        return $results;
    }

    /**
     * Get a list of all licences used in a document.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedRubrics(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT r
            FROM '.CfRubric::class.' r
            JOIN r.criteria c
            JOIN c.item i
            WHERE i.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        return $query->getResult($format);
    }

    /**
     * Get a list of all association groups used in an LsDoc.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllDocAssociationGroups(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_OBJECT): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT ag
            FROM '.LsDefAssociationGrouping::class.' ag
            WHERE ag.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        return $query->getResult($format);
    }

    /**
     * Get a list of all associations for an LsDoc where the nodes are known items.
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllAssociationsForCapturedNodes(LsDoc $lsDoc): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT a, ag, adi, add, odi, odd
            FROM '.LsAssociation::class.' a INDEX BY a.id
            LEFT JOIN a.group ag
            LEFT JOIN a.originLsItem odi WITH odi.lsDoc = :lsDocId
            LEFT JOIN a.originLsDoc odd WITH odd.id = :lsDocId
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE a.lsDoc = :lsDocId
              AND (odi.id IS NOT NULL OR odd.id IS NOT NULL)
              AND (adi.id IS NOT NULL OR add.id IS NOT NULL)
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setMaxResults(self::MAX_RESULTS);

        return $query->getResult(Query::HYDRATE_ARRAY);
    }

    public function findAssociatedDocs(LsDoc $lsDoc): array
    {
        $docs = [];
        $docId = $lsDoc->getId();

        $joinConfigs = [
            ['d.lsItems', 'i.associations', 'a.destinationLsItem', 'ref.lsDoc = :doc'],
            ['d.lsItems', 'i.associations', 'a.originLsItem', 'ref.lsDoc = :doc'],
            ['d.lsItems', 'i.associations', 'a.destinationLsDoc', 'ref.id = :doc'],
            ['d.lsItems', 'i.associations', 'a.originLsDoc', 'ref.id = :doc'],
            ['d.docAssociations', null, 'a.destinationLsItem', 'ref.lsDoc = :doc'],
            ['d.docAssociations', null, 'a.originLsItem', 'ref.lsDoc = :doc'],
        ];

        foreach ($joinConfigs as [$itemJoin, $assocJoinField, $targetJoinField, $where]) {
            $qb = $this->createQueryBuilder('d')
                ->select('d.id, d.identifier, d.uri, d.title')
                ->distinct()
                ->join($itemJoin, 'i');

            if (null !== $assocJoinField) {
                $qb->join($assocJoinField, 'a');
            }

            $qb->join($targetJoinField, 'ref')
                ->where($where)
                ->setParameter('doc', $docId);

            $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
            foreach ($results as $doc) {
                $docs[$doc['identifier']] = [
                    'autoLoad' => 'true',
                    'url' => $doc['uri'],
                    'title' => $doc['title'],
                ];
            }
        }

        return $docs;
    }

    /**
     * Get a list of all items for an LsDoc.
     *
     * @psalm-param AbstractQuery::HYDRATE_* $format
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findItemsForExportDoc(LsDoc $lsDoc, int $format = AbstractQuery::HYDRATE_ARRAY): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, t,
              CASE WHEN a.sequenceNumber IS NULL THEN 1 ELSE 0 END as HIDDEN seq_is_null,
              a.sequenceNumber as HIDDEN seq
            FROM '.LsItem::class.' i INDEX BY i.id
            LEFT JOIN i.itemType t
            LEFT JOIN i.associations a WITH a.lsDoc = :lsDocId AND a.type = :childOfType
            WHERE i.lsDoc = :lsDocId
            ORDER BY seq_is_null ASC, seq ASC, i.listEnumInSource ASC, i.humanCodingScheme
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);
        $query->setMaxResults(self::MAX_RESULTS);

        return $query->getResult($format);
    }

    /**
     * Get a list of all items for an LsDoc.
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findAssociationsForExportDoc(LsDoc $lsDoc): array
    {
        $query = $this->getEntityManager()->createQuery(sprintf('
            SELECT a, g, oi.id AS oi_id, oi.identifier AS oi_identifier, oi.lsDocIdentifier AS oi_lsDocIdentifier, di.id AS di_id, di.identifier AS di_identifier, di.lsDocIdentifier as di_lsDocIdentifier
            FROM %s a INDEX BY a.id
            LEFT JOIN a.group g
            LEFT JOIN a.originLsItem oi
            LEFT JOIN a.destinationLsItem di
            WHERE a.lsDoc = :lsDocId
            ORDER BY a.sequenceNumber ASC
        ', LsAssociation::class));
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setMaxResults(self::MAX_RESULTS);

        return array_map(
            $this->mapExportAssociation(...),
            $query->getResult(AbstractQuery::HYDRATE_ARRAY)
        );
    }

    private function mapExportAssociation(array $rec): array
    {
        $ret = $rec[0];
        $ret['originLsItem'] = [
            'id' => $rec['oi_id'],
            'identifier' => $rec['oi_identifier'],
            'lsDocIdentifier' => $rec['oi_lsDocIdentifier'],
        ];
        $ret['destinationLsItem'] = [
            'id' => $rec['di_id'],
            'identifier' => $rec['di_identifier'],
            'lsDocIdentifier' => $rec['di_lsDocIdentifier'],
        ];

        return $ret;
    }

    private function getSortValue(LsDoc $doc, string $sortField): string
    {
        return match ($sortField) {
            'd.title' => $doc->getTitle() ?? '',
            'd.creator' => $doc->getCreator() ?? '',
            'd.identifier' => $doc->getIdentifier(),
            'd.changedAt' => $doc->getChangedAt()->format('c') ?? '',
            default => $doc->getIdentifier(),
        };
    }

    public function findDocumentsWithPagination(PaginationDto $pagination, DocumentFilterDto $filter): DocumentListResponseDto
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            $user = null;
        }

        $qb = $this->createQueryBuilder('d')
            ->distinct()
            ->select('d')
            ->leftJoin('d.mirroredFramework', 'm');

        $this->applyUserAccessConditions($qb, $user);
        $this->applyDocumentFilters($qb, $filter);
        $this->applyPaginationCursor($qb, $pagination, $filter);

        $qb->orderBy($filter->sortField, $filter->sortOrder);
        if ('d.creator' === $filter->sortField) {
            $qb->addOrderBy('d.title', $filter->sortOrder);
        }
        $qb->addOrderBy('d.identifier', $filter->sortOrder);
        $qb->setMaxResults($pagination->size + 1);

        $documents = $qb->getQuery()->getResult() ?? [];

        $documentCount = count($documents);
        $hasMore = $documentCount > $pagination->size;
        if ($hasMore) {
            array_pop($documents);
            --$documentCount;
        }

        $this->hydrateDocumentRelations($documents);

        $paginationData = new DocumentPaginationResponseDto($hasMore, null, $documentCount);

        if (!empty($documents) && $hasMore) {
            $lastDoc = end($documents);
            $title = 'd.creator' === $filter->sortField ? ($lastDoc->getTitle() ?? '') : null;
            $paginationData->nextCursor = $pagination->encodeCursor(
                $this->getSortValue($lastDoc, $filter->sortField),
                $lastDoc->getIdentifier(),
                $title
            );
        }

        return new DocumentListResponseDto($documents, $paginationData);
    }

    private function applyUserAccessConditions(QueryBuilder $qb, ?User $user): void
    {
        if (null !== $user) {
            if (!$this->security->isGranted(Permission::FRAMEWORK_EDIT_ALL)) {
                $isEditor = $this->security->isGranted('ROLE_EDITOR');
                $qb->leftJoin('d.docAcls', 'acls', 'WITH', 'acls.user = :user')
                    ->orWhere('(m.visible IS NULL OR m.visible = 1) AND (d.adoptionStatus != :privateDraft OR d.adoptionStatus IS NULL)')
                    ->orWhere('(m.visible IS NOT NULL AND 1 = :isEditor)')
                    ->orWhere('(d.org = :org OR d.user = :user OR acls.access = 1) AND (acls.access IS NULL OR acls.access != 0)')
                    ->setParameter('isEditor', $isEditor ? 1 : 0)
                    ->setParameter('user', $user)
                    ->setParameter('org', $user->getOrg())
                    ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);
            }
        }
        if (null === $user) {
            $qb->andWhere('m.visible IS NULL OR m.visible = 1')
                ->andWhere('(d.adoptionStatus != :privateDraft OR d.adoptionStatus IS NULL)')
                ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);
        }
    }

    private function applyDocumentFilters(QueryBuilder $qb, DocumentFilterDto $filter): void
    {
        if (null !== $filter->creator) {
            $qb->andWhere('LOWER(d.creator) LIKE LOWER(:creator)')
               ->setParameter('creator', LikeQueryHelper::contains($filter->creator));
        }
        if (null !== $filter->title) {
            $qb->andWhere('LOWER(d.title) LIKE LOWER(:title)')
               ->setParameter('title', LikeQueryHelper::contains($filter->title));
        }
        if (null !== $filter->adoptionStatus) {
            $qb->andWhere('LOWER(d.adoptionStatus) = LOWER(:adoptionStatus)')
               ->setParameter('adoptionStatus', $filter->adoptionStatus);
        }
        if (null !== $filter->subject) {
            $qb->leftJoin('d.subjects', 's');
            $qb->andWhere('LOWER(d.subject) LIKE LOWER(:subject) OR LOWER(s.title) = LOWER(:subject)')
               ->setParameter('subject', LikeQueryHelper::escapeLike($filter->subject));
        }
        if (null !== $filter->language) {
            $qb->andWhere('LOWER(d.language) = LOWER(:language)')
               ->setParameter('language', $filter->language);
        }
        if (null !== $filter->publisher) {
            $qb->andWhere('LOWER(d.publisher) LIKE LOWER(:publisher)')
               ->setParameter('publisher', LikeQueryHelper::contains($filter->publisher));
        }
    }

    private function applyPaginationCursor(QueryBuilder $qb, PaginationDto $pagination, DocumentFilterDto $filter): void
    {
        if (null === $pagination->after) {
            return;
        }

        $decodedAfter = $pagination->decodeCursor($pagination->after);
        if ('d.creator' === $filter->sortField) {
            $qb->andWhere('(d.creator > :afterSortValue OR (d.creator = :afterSortValue AND (d.title > :afterTitle OR (d.title = :afterTitle AND d.identifier > :afterIdentifier))))')
                ->setParameter('afterSortValue', $decodedAfter['sortValue'])
                ->setParameter('afterTitle', $decodedAfter['title'] ?? '')
                ->setParameter('afterIdentifier', $decodedAfter['identifier']);
        } else {
            $qb->andWhere('('.$filter->sortField.' > :afterSortValue OR ('.$filter->sortField.' = :afterSortValue AND d.identifier > :afterIdentifier))')
                ->setParameter('afterSortValue', $decodedAfter['sortValue'])
                ->setParameter('afterIdentifier', $decodedAfter['identifier']);
        }
    }

    private function hydrateDocumentRelations(array $documents): void
    {
        if (empty($documents)) {
            return;
        }

        $this->createQueryBuilder('d')
            ->select('d', 's', 'l', 'ft')
            ->leftJoin('d.subjects', 's')
            ->leftJoin('d.licence', 'l')
            ->leftJoin('d.frameworkType', 'ft')
            ->where('d.id IN (:documents)')
            ->setParameter('documents', $documents)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find related documents for a given document with permission filtering.
     *
     * Refactored implementation that:
     * 1. Executes 6 separate queries (one for each original UNION ALL clause)
     * 2. Uses NOT IN clauses to avoid duplicates and improve performance
     * 3. Combines results in PHP
     * 4. Maintains the same ACL filtering and return type
     *
     * @return LsDoc[]
     */
    public function findRelatedDocuments(LsDoc $lsDoc, ?User $user = null): array
    {
        $docId = $lsDoc->getId();
        $foundIds = [];

        $queries = [
            $this->createItemJoinQuery($docId, 'a.destinationLsItem = i', 'a.originLsItem = i2', 'i2.lsDoc = :docId'),
            $this->createItemJoinQuery($docId, 'a.originLsItem = i', 'a.destinationLsItem = i2', 'i2.lsDoc = :docId'),
            $this->createItemJoinQuery($docId, 'a.originLsItem = i', null, 'a.destinationLsDoc = :docId'),
            $this->createItemJoinQuery($docId, 'a.destinationLsItem = i', null, 'a.originLsDoc = :docId'),
            $this->createDocAssocQuery($docId, 'a.destinationLsItem = i2', 'i2.lsDoc = :docId'),
            $this->createDocAssocQuery($docId, 'a.originLsItem = i2', 'i2.lsDoc = :docId'),
        ];

        foreach ($queries as $qb) {
            $this->addAclConditions($qb, $user);
            if (!empty($foundIds)) {
                $qb->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Framework\LsDoc d2 WHERE d2.id IN (:foundIds) AND d2.id = d.id)')
                    ->setParameter('foundIds', array_keys($foundIds));
            }
            $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
            foreach ($results as $row) {
                $foundIds[(int) $row['id']] = true;
            }
        }

        $docIds = array_keys($foundIds);
        if (empty($docIds)) {
            return [];
        }

        return $this->createQueryBuilder('d')
            ->select('d, s')
            ->leftJoin('d.subjects', 's')
            ->where('d.id IN (:docIds)')
            ->setParameter('docIds', $docIds)
            ->orderBy('d.creator', 'ASC')
            ->addOrderBy('d.title', 'ASC')
            ->addOrderBy('d.adoptionStatus', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function createItemJoinQuery(int $docId, string $assocJoin, ?string $item2Join, string $whereClause): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsItem::class, 'i', 'WITH', 'i.lsDoc = d')
            ->join(LsAssociation::class, 'a', 'WITH', $assocJoin);

        if (null !== $item2Join) {
            $qb->join(LsItem::class, 'i2', 'WITH', $item2Join);
        }

        $qb->where($whereClause)
            ->setParameter('docId', $docId);

        return $qb;
    }

    private function createDocAssocQuery(int $docId, string $itemJoin, string $whereClause): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsAssociation::class, 'a', 'WITH', 'a.lsDoc = d')
            ->join(LsItem::class, 'i2', 'WITH', $itemJoin)
            ->where($whereClause)
            ->setParameter('docId', $docId);
    }

    /**
     * Add ACL conditions to a QueryBuilder for filtering documents by user permissions.
     */
    private function addAclConditions(QueryBuilder $qb, ?User $user): void
    {
        $qb->leftJoin('d.mirroredFramework', 'm');
        $this->applyUserAccessConditions($qb, $user);
    }
}
