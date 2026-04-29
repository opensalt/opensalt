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
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\AbstractQuery;
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

        // Apply user/organization filtering with extended access control
        if (null !== $user) {
            if (!$this->security->isGranted(Permission::FRAMEWORK_EDIT_ALL)) {
                $isEditor = $this->security->isGranted('ROLE_EDITOR');
                $qb->leftJoin('d.docAcls', 'acls', 'WITH', 'acls.user = :user')
                    ->orWhere('(m.visible IS NULL OR m.visible = 1) AND (d.adoptionStatus != :privateDraft)')
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
                ->andWhere('d.adoptionStatus != :privateDraft')
                ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);
        }

        $qb->orderBy('d.creator', 'ASC')
            ->addOrderBy('d.title', 'ASC')
            ->addOrderBy('d.adoptionStatus', 'ASC');

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
            ;

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

        /** @var array $results */
        $results = $query->getResult(Query::HYDRATE_ARRAY);

        foreach ($results as $key => $result) {
            $results[$key]['children'] = [];
        }

        foreach ($results as $key => $result) {
            foreach ($result['associations'] as $association) {
                if (!empty($association['destinationLsItem'])) {
                    $parent = $association['destinationLsItem'];
                    $results[$parent['id']]['children'][] = $result;

                    if (!empty($association['group'])) {
                        $results[$key]['assoc'][$parent['id']] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => $association['group']['id'],
                        ];
                    } else {
                        $results[$key]['assoc'][$parent['id']] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => '',
                        ];
                    }
                } elseif (!empty($association['destinationLsDoc'])) {
                    if (!empty($association['group'])) {
                        $results[$key]['assoc']['doc'] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => $association['group']['id'],
                        ];
                    } else {
                        $results[$key]['assoc']['doc'] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => '',
                        ];
                    }
                }
            }
        }

        foreach ($results as $key => $result) {
            if (!empty($results[$key]['children'])) {
                $this->rankItems($results[$key]['children']);
            }
        }

        return $results;
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
                   IDENTITY(di.lsDoc) as destDocId
            FROM '.LsAssociation::class.' a
            LEFT JOIN a.group g
            JOIN a.originLsItem oi
            LEFT JOIN a.destinationLsItem di
            WHERE a.lsDoc = :viewedDocId
              AND a.type = :childOfType
        ');
        $query->setParameter('viewedDocId', $viewedDocId);
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);

        $childOfAssocs = $query->getResult(Query::HYDRATE_ARRAY);

        $query = $em->createQuery('
            SELECT a.identifier as assocIdentifier, a.sequenceNumber,
                   g.identifier as groupIdentifier,
                   oi.identifier as originIdentifier, oi.uri as originUri,
                   oi.humanCodingScheme as originHcs, oi.fullStatement as originFs,
                   oi.abbreviatedStatement as originAbs, oi.listEnumInSource as originLe,
                   IDENTITY(oi.lsDoc) as originDocId,
                   IDENTITY(oi.itemType) as originItemTypeId,
                   oi.changedAt as originChangedAt
            FROM '.LsAssociation::class.' a
            LEFT JOIN a.group g
            JOIN a.originLsItem oi
            JOIN a.destinationLsDoc dd WITH dd.id = :viewedDocId
            WHERE a.lsDoc = :viewedDocId
              AND a.type = :childOfType
        ');
        $query->setParameter('viewedDocId', $viewedDocId);
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);

        $docChildAssocs = $query->getResult(Query::HYDRATE_ARRAY);

        $itemRepo = $em->getRepository(LsItem::class);

        $allItemsQuery = $em->createQuery('
            SELECT i.identifier, i.uri, i.humanCodingScheme, i.fullStatement,
                   i.abbreviatedStatement, i.listEnumInSource, i.changedAt,
                   i.discriminator, i.extensions,
                   IDENTITY(i.lsDoc) as lsDoc,
                   IDENTITY(i.itemType) as itemType
            FROM '.LsItem::class.' i
            WHERE i.lsDoc = :docId
        ');
        $allItemsQuery->setParameter('docId', $viewedDocId);
        $allItems = $allItemsQuery->getResult(Query::HYDRATE_ARRAY);

        $itemDataMap = [];
        $itemIds = [];
        foreach ($allItems as $item) {
            $itemDataMap[$item['identifier']] = $item;
            $itemIds[$item['identifier']] = $item['identifier'];
        }

        $itemLicenceMap = [];
        $itemSubjectMap = [];
        if ([] !== $itemIds && !$lightweight) {
            $itemIdsByInternalId = [];
            foreach ($allItems as $item) {
                $itemIdsByInternalId[$item['identifier']] = $item['identifier'];
            }

            $licenceQuery = $em->createQuery('
                SELECT i.identifier as itemIdentifier, l.identifier as licenceIdentifier,
                       l.uri as licenceUri, l.title as licenceTitle
                FROM '.LsItem::class.' i
                JOIN i.licence l
                WHERE i.lsDoc = :docId
            ');
            $licenceQuery->setParameter('docId', $viewedDocId);
            $licenceResults = $licenceQuery->getResult(Query::HYDRATE_ARRAY);
            foreach ($licenceResults as $lr) {
                $itemLicenceMap[$lr['itemIdentifier']] = [
                    'identifier' => $lr['licenceIdentifier'],
                    'uri' => $lr['licenceUri'],
                    'title' => $lr['licenceTitle'],
                ];
            }

            $subjectQuery = $em->createQuery('
                SELECT i.identifier as itemIdentifier, s.identifier as subjectIdentifier,
                       s.uri as subjectUri, s.title as subjectTitle
                FROM '.LsItem::class.' i
                JOIN i.subjects s
                WHERE i.lsDoc = :docId
            ');
            $subjectQuery->setParameter('docId', $viewedDocId);
            $subjectResults = $subjectQuery->getResult(Query::HYDRATE_ARRAY);
            foreach ($subjectResults as $sr) {
                $itemSubjectMap[$sr['itemIdentifier']][] = [
                    'identifier' => $sr['subjectIdentifier'],
                    'uri' => $sr['subjectUri'],
                    'title' => $sr['subjectTitle'],
                ];
            }
        }

        $parentMap = [];
        $assocMap = [];
        $childIds = [];
        $foreignItemIdentifiers = [];

        foreach ($childOfAssocs as $row) {
            $originId = $row['originIdentifier'];
            $destId = $row['destIdentifier'] ?? $row['destNodeIdentifier'];

            $parentMap[$originId] = $destId;
            $assocMap[$originId] = [
                'assocIdentifier' => $row['assocIdentifier'],
                'sequenceNumber' => $row['sequenceNumber'],
                'groupIdentifier' => $row['groupIdentifier'],
                'originFs' => $row['originFs'],
                'originHcs' => $row['originHcs'],
                'originAbs' => $row['originAbs'],
            ];
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
            $assocMap[$originId] = [
                'assocIdentifier' => $row['assocIdentifier'],
                'sequenceNumber' => $row['sequenceNumber'],
                'groupIdentifier' => $row['groupIdentifier'],
                'originFs' => $row['originFs'],
                'originHcs' => $row['originHcs'],
                'originAbs' => $row['originAbs'],
            ];
            $childIds[$originId] = true;

            if (null !== $row['originDocId'] && (int) $row['originDocId'] !== $viewedDocId) {
                $foreignItemIdentifiers[$originId] = true;
            }
        }

        $foreignItems = [];
        if ([] !== $foreignItemIdentifiers) {
            $foreignQuery = $em->createQuery('
                SELECT i.identifier, i.uri, i.humanCodingScheme, i.fullStatement,
                       i.abbreviatedStatement, i.listEnumInSource, i.changedAt,
                       i.discriminator, i.extensions,
                       IDENTITY(i.lsDoc) as lsDoc,
                       IDENTITY(i.itemType) as itemType
                FROM '.LsItem::class.' i
                WHERE i.identifier IN (:ids)
            ');
            $foreignQuery->setParameter('ids', array_keys($foreignItemIdentifiers));
            $foreignResult = $foreignQuery->getResult(Query::HYDRATE_ARRAY);

            foreach ($foreignResult as $fi) {
                $foreignItems[$fi['identifier']] = $fi;
            }
        }

        $itemTypeRepo = $em->getRepository(LsDefItemType::class);
        $itemTypeCache = [];

        $buildNode = function (string $identifier, bool $isCrossFramework = false) use (
            &$buildNode, $itemDataMap, $foreignItems, $parentMap, $assocMap,
            $viewedDocId, $viewedDocIdentifier, $childIds, $lightweight,
            $itemTypeRepo, &$itemTypeCache, $em,
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
                $assoc = $assocMap[$identifier] ?? [];
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
                } else {
                    $node['discriminator'] = 0;
                    $node['extensions'] = [];
                }

                return $node;
            }

            $itemDoc = $item['lsDoc'] ?? null;
            $docTitle = null;
            if (null !== $itemDoc && (int) $itemDoc !== $viewedDocId) {
                $docEntity = $em->getRepository(LsDoc::class)->find($itemDoc);
                if (null !== $docEntity) {
                    $docId = $docEntity->getIdentifier();
                    $docTitle = $docEntity->getTitle();
                }
            }

            if ($lightweight) {
                $node = [
                    'identifier' => $identifier,
                    'documentIdentifier' => $docId,
                    'documentTitle' => $docTitle,
                    'humanCodingScheme' => $item['humanCodingScheme'] ?? null,
                    'fullStatement' => $item['fullStatement'] ?? null,
                    'abbreviatedStatement' => $item['abbreviatedStatement'] ?? null,
                    'isCrossFramework' => $isForeign,
                    'discriminator' => $item['discriminator'] ?? 0,
                    'extensions' => $item['extensions'] ?? [],
                    'children' => [],
                ];
            } else {
                $itemTypeName = null;
                if (isset($item['itemType'])) {
                    $itId = $item['itemType'];
                    if (!isset($itemTypeCache[$itId])) {
                        $itEntity = $itemTypeRepo->find($itId);
                        $itemTypeCache[$itId] = $itEntity?->getTitle();
                    }
                    $itemTypeName = $itemTypeCache[$itId];
                }

                $node = [
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
                    'licenseURI' => $itemLicenceMap[$identifier] ?? null,
                    'subjectURI' => $itemSubjectMap[$identifier] ?? [],
                    'children' => [],
                ];
            }

            return $node;
        };

        $childrenMap = [];
        foreach ($parentMap as $childId => $parentId) {
            if (!isset($childrenMap[$parentId])) {
                $childrenMap[$parentId] = [];
            }
            $seq = $assocMap[$childId]['sequenceNumber'] ?? null;
            $childrenMap[$parentId][] = ['id' => $childId, 'seq' => $seq];
        }

        foreach ($childrenMap as $parentId => &$children) {
            usort($children, static function (array $a, array $b): int {
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
            });
        }
        unset($children);

        $buildTree = function (string $parentId) use (&$buildTree, &$buildNode, $childrenMap, $itemDataMap, $foreignItems, $viewedDocId): array {
            $result = [];
            if (!isset($childrenMap[$parentId])) {
                return $result;
            }

            foreach ($childrenMap[$parentId] as $childInfo) {
                $childId = $childInfo['id'];
                $isForeign = false;
                if (isset($itemDataMap[$childId])) {
                    $itemDocId = $itemDataMap[$childId]['lsDoc'] ?? null;
                    if (null !== $itemDocId && (int) $itemDocId !== $viewedDocId) {
                        $isForeign = true;
                    }
                } elseif (isset($foreignItems[$childId])) {
                    $isForeign = true;
                }

                $node = $buildNode($childId, $isForeign);
                if (null === $node) {
                    continue;
                }

                $node['children'] = $buildTree($childId);
                $result[] = $node;
            }

            return $result;
        };

        $tree = $buildTree($viewedDocIdentifier);

        $orphanItemIds = [];
        foreach ($itemDataMap as $identifier => $item) {
            if (!isset($childIds[$identifier])) {
                $orphanItemIds[] = $identifier;
            }
        }

        foreach ($orphanItemIds as $orphanId) {
            $item = $itemDataMap[$orphanId];
            $itemTypeName = null;
            if (isset($item['itemType'])) {
                $itId = $item['itemType'];
                if (!isset($itemTypeCache[$itId])) {
                    $itEntity = $itemTypeRepo->find($itId);
                    $itemTypeCache[$itId] = $itEntity?->getTitle();
                }
                $itemTypeName = $itemTypeCache[$itId];
            }

            if ($lightweight) {
                $tree[] = [
                    'identifier' => $orphanId,
                    'documentIdentifier' => $viewedDocIdentifier,
                    'humanCodingScheme' => $item['humanCodingScheme'] ?? null,
                    'fullStatement' => $item['fullStatement'] ?? null,
                    'abbreviatedStatement' => $item['abbreviatedStatement'] ?? null,
                    'isCrossFramework' => false,
                    'discriminator' => $item['discriminator'] ?? 0,
                    'extensions' => $item['extensions'] ?? [],
                    'children' => [],
                ];
            } else {
                $tree[] = [
                    'identifier' => $orphanId,
                    'uri' => $item['uri'] ?? null,
                    'documentIdentifier' => $viewedDocIdentifier,
                    'humanCodingScheme' => $item['humanCodingScheme'] ?? null,
                    'fullStatement' => $item['fullStatement'] ?? null,
                    'abbreviatedStatement' => $item['abbreviatedStatement'] ?? null,
                    'listEnumeration' => $item['listEnumInSource'] ?? null,
                    'itemType' => $itemTypeName,
                    'sequenceNumber' => null,
                    'lastChangeDateTime' => ($item['changedAt'] ?? null) instanceof \DateTimeInterface
                        ? $item['changedAt']->format('c')
                        : $item['changedAt'],
                    'childOfAssociationIdentifier' => null,
                    'associationGroupIdentifier' => null,
                    'isCrossFramework' => false,
                    'isUnresolved' => false,
                    'discriminator' => $item['discriminator'] ?? 0,
                    'extensions' => $item['extensions'] ?? [],
                    'children' => [],
                ];
            }
        }

        $definitions = [];
        if (!$lightweight) {
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
            $definitions['CFSubjects'] = array_values(array_map(static function ($s): array {
                if (is_array($s)) {
                    return $s;
                }

                return [
                    'identifier' => method_exists($s, 'getIdentifier') ? $s->getIdentifier() : null,
                    'uri' => method_exists($s, 'getUri') ? $s->getUri() : null,
                    'title' => method_exists($s, 'getTitle') ? $s->getTitle() : null,
                ];
            }, is_array($subjects) ? $subjects : $subjects->toArray()));

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
        }

        return [
            'tree' => $tree,
            'definitions' => $definitions,
        ];
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

        $stmt = <<<'xENDx'
DELETE FROM salt_object_lock
 WHERE doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting associations');
        $stmt = <<<'xENDx'
DELETE FROM ls_association
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $stmt = <<<'xENDx'
DELETE FROM ls_association
 WHERE origin_lsitem_id IN (
      SELECT i.id
        FROM ls_item i
       WHERE i.ls_doc_id = :lsDocId
    )
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $stmt = <<<'xENDx'
DELETE FROM ls_association
 WHERE destination_lsitem_id IN (
      SELECT i.id
        FROM ls_item i
       WHERE i.ls_doc_id = :lsDocId
    )
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting association groups');
        $stmt = <<<'xENDx'
DELETE FROM ls_def_association_grouping
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting rubric references to items');
        $stmt = <<<'xENDx'
UPDATE rubric_criterion
   SET ls_item_id = NULL
 WHERE ls_item_id IN (
   SELECT id
     FROM ls_item
    WHERE ls_doc_id = :lsDocId
 )
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting item subject links');
        $stmt = <<<'xENDx'
DELETE FROM ls_item_subject
 WHERE ls_item_id IN (
   SELECT id
     FROM ls_item
    WHERE ls_doc_id = :lsDocId
 )
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting item concept links');
        $stmt = <<<'xENDx'
DELETE FROM ls_item_concept
 WHERE ls_item_id IN (
   SELECT id
     FROM ls_item
    WHERE ls_doc_id = :lsDocId
 )
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting items');
        $stmt = <<<'xENDx'
DELETE FROM ls_item
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting document subjects');
        $stmt = <<<'xENDx'
DELETE FROM ls_doc_subject
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting document import logs');
        $stmt = <<<'xENDx'
DELETE FROM import_logs
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting acls');
        $stmt = <<<'xENDx'
DELETE FROM salt_user_doc_acl
 WHERE doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting document attributes');
        $stmt = <<<'xENDx'
DELETE FROM ls_doc_attribute
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

        $progressCallback('Deleting document');
        $stmt = <<<'xENDx'
DELETE FROM ls_doc
 WHERE id = :lsDocId
;
xENDx;
        $preparedStatement = $conn->prepare($stmt);
        $preparedStatement->bindValue('lsDocId', $lsDoc->getId());
        $preparedStatement->executeStatement();

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
            $newLsDoc = new LsDoc();
            $newLsDoc->setTitle($oldLsDoc->getTitle().' - Derived');
            $newLsDoc->setCreator($oldLsDoc->getCreator());
            $newLsDoc->setVersion($oldLsDoc->getVersion());
            $newLsDoc->setDescription($oldLsDoc->getDescription());
            $newLsDoc->setSubject($oldLsDoc->getSubject());
            $newLsDoc->setNote($oldLsDoc->getNote());
            $newLsDoc->setLanguage($oldLsDoc->getLanguage());
            $newLsDoc->setOrg($oldLsDoc->getOrg());
            $newLsDoc->setUser($oldLsDoc->getUser());
            $newLsDoc->setLicence($oldLsDoc->getLicence());
        }

        foreach ($oldLsDoc->getAssociationGroupings() as $assocGroup) {
            $assocGroup->duplicateToLsDoc($newLsDoc);
        }

        $em->persist($newLsDoc);

        return $newLsDoc;
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

        foreach ($fromDoc->getAssociations() as $oldAssoc) {
            $newAssoc = $toDoc->createAssociation();
            $newAssoc->setOriginLsItem($item);
            $newAssoc->setType($oldAssoc->getType());
            $newAssoc->setDestination($oldAssoc->getDestination(), $oldAssoc->getDestinationNodeIdentifier());
            $item->addAssociation($newAssoc);
            $em->persist($newAssoc);
        }

        foreach ($fromDoc->getTopLsItems() as $oldItem) {
            $newItem = $oldItem->duplicateToLsDoc($toDoc);
            $item->addChild($newItem);
        }

        $progressCallback('Done');
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
            $query->setMaxResults($limit);
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
            $query->setMaxResults($limit);
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
            $query->setMaxResults($limit);
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
        // get licences for items
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT l
            FROM '.LsDefLicence::class.' l INDEX BY l.id, '.LsItem::class.' i
            WHERE (i.lsDoc = :lsDocId AND i.licence = l)
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        // get licence for the doc
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT l
            FROM '.LsDefLicence::class.' l INDEX BY l.id, '.LsDoc::class.' d
            WHERE (d.id = :lsDocId AND d.licence = l)
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $docResults = $query->getResult($format);

        if (AbstractQuery::HYDRATE_ARRAY === $format) {
            foreach ($docResults as $id => $result) {
                $results[$id] = $result;
            }
        } else {
            foreach ($docResults as $result) {
                $results[$result->getId()] = $result;
            }
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

        return $query->getResult(Query::HYDRATE_ARRAY);
    }

    public function findAssociatedDocs(LsDoc $lsDoc): array
    {
        $docs = [];

        // Where the framework has a destination item in the document
        $qb = $this->createQueryBuilder('d')
            ->select('d.id, d.identifier, d.uri, d.title')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.destinationLsItem', 'i2')
            ->where('i2.lsDoc = :doc')
            ->setParameter('doc', $lsDoc->getId())
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        // Where the framework has an origin item in the document
        $qb = $this->createQueryBuilder('d')
            ->select('d.id, d.identifier, d.uri, d.title')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.originLsItem', 'i2')
            ->where('i2.lsDoc = :doc')
            ->setParameter('doc', $lsDoc->getId())
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        // Where the framework has a destination document as the document
        $qb = $this->createQueryBuilder('d')
            ->select('d.id, d.identifier, d.uri, d.title')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.destinationLsDoc', 'd2')
            ->where('d2.id = :doc')
            ->setParameter('doc', $lsDoc->getId())
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        // Where the framework has an origin document as the document
        $qb = $this->createQueryBuilder('d')
            ->select('d.id, d.identifier, d.uri, d.title')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.originLsDoc', 'd2')
            ->where('d2.id = :doc')
            ->setParameter('doc', $lsDoc->getId())
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        // Where there is an association belonging to the framework to an item in the document
        $qb = $this->createQueryBuilder('d')
            ->select('d.id, d.identifier, d.uri, d.title')
            ->distinct()
            ->join('d.docAssociations', 'a')
            ->join('a.destinationLsItem', 'i2')
            ->where('i2.lsDoc = :doc')
            ->setParameter('doc', $lsDoc->getId())
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        // Where there is an association belonging to the framework to an item in the document
        $qb = $this->createQueryBuilder('d')
            ->select('d.id, d.identifier, d.uri, d.title')
            ->distinct()
            ->join('d.docAssociations', 'a')
            ->join('a.originLsItem', 'i2')
            ->where('i2.lsDoc = :doc')
            ->setParameter('doc', $lsDoc->getId())
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
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

        return array_map(
            function (array $rec): array {
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
            },
            $query->getResult(AbstractQuery::HYDRATE_ARRAY)
        );
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

        // Apply user/organization filtering with extended access control
        if (null !== $user) {
            if (!$this->security->isGranted(Permission::FRAMEWORK_EDIT_ALL)) {
                $isEditor = $this->security->isGranted('ROLE_EDITOR');
                $qb->leftJoin('d.docAcls', 'acls', 'WITH', 'acls.user = :user')
                    ->orWhere('(m.visible IS NULL OR m.visible = 1) AND (d.adoptionStatus != :privateDraft)')
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
                ->andWhere('d.adoptionStatus != :privateDraft')
                ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);
        }

        // Apply filters
        if (null !== $filter->creator) {
            $qb->andWhere('LOWER(d.creator) LIKE LOWER(:creator)')
               ->setParameter('creator', '%'.$filter->creator.'%');
        }

        if (null !== $filter->title) {
            $qb->andWhere('LOWER(d.title) LIKE LOWER(:title)')
               ->setParameter('title', '%'.$filter->title.'%');
        }

        if (null !== $filter->adoptionStatus) {
            $qb->andWhere('LOWER(d.adoptionStatus) = LOWER(:adoptionStatus)')
               ->setParameter('adoptionStatus', $filter->adoptionStatus);
        }

        if (null !== $filter->subject) {
            $qb->leftJoin('d.subjects', 's');
            $qb->andWhere('LOWER(d.subject) LIKE LOWER(:subject) OR LOWER(s.title) = LOWER(:subject)')
               ->setParameter('subject', $filter->subject);
        }

        if (null !== $filter->language) {
            $qb->andWhere('LOWER(d.language) = LOWER(:language)')
               ->setParameter('language', $filter->language);
        }

        if (null !== $filter->publisher) {
            $qb->andWhere('LOWER(d.publisher) LIKE LOWER(:publisher)')
               ->setParameter('publisher', '%'.$filter->publisher.'%');
        }

        // Apply cursor-based pagination
        if (null !== $pagination->after) {
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

        // Apply sorting
        $qb->orderBy($filter->sortField, $filter->sortOrder);
        if ('d.creator' === $filter->sortField) {
            $qb->addOrderBy('d.title', $filter->sortOrder);
        }
        $qb->addOrderBy('d.identifier', $filter->sortOrder); // Secondary sort by identifier for consistent pagination

        // Apply limit
        $qb->setMaxResults($pagination->size + 1); // +1 to check if there are more results

        $documents = $qb->getQuery()->getResult() ?? [];

        // Check if there are more results
        $documentCount = count($documents);
        $hasMore = $documentCount > $pagination->size;
        if ($hasMore) {
            array_pop($documents); // Remove the extra item
            --$documentCount;
        }

        // Add subject and licence data to objects (don't in original query to keep limit count correct)
        $this->createQueryBuilder('d')
            ->select('d', 's', 'l', 'ft')
            ->leftJoin('d.subjects', 's')
            ->leftJoin('d.licence', 'l')
            ->leftJoin('d.frameworkType', 'ft')
            ->where('d.id IN (:documents)')
            ->setParameter('documents', $documents)
            ->getQuery()
            ->getResult();

        // Create pagination metadata
        $paginationData = new DocumentPaginationResponseDto(
            $hasMore,
            null,
            $documentCount // This is approximate for performance
        );

        if (!empty($documents)) {
            $lastDoc = end($documents);

            if ($hasMore) {
                $title = null;
                if ('d.creator' === $filter->sortField) {
                    $title = $lastDoc->getTitle() ?? '';
                }
                $paginationData->nextCursor = $pagination->encodeCursor(
                    $this->getSortValue($lastDoc, $filter->sortField),
                    $lastDoc->getIdentifier(),
                    $title
                );
            }
        }

        return new DocumentListResponseDto($documents, $paginationData);
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

        // Collect document IDs from each query, avoiding duplicates
        $docIds = [];
        $foundIds = [];

        // Query 1: Items where the document has a destination item
        // Original: SELECT DISTINCT i.ls_doc_id FROM ls_item i
        //   INNER JOIN ls_association a ON a.destination_lsitem_id = i.id
        //   INNER JOIN ls_item i2 ON i2.id = a.origin_lsitem_id
        //   WHERE i2.ls_doc_id = :docId
        $qb = $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsItem::class, 'i', 'WITH', 'i.lsDoc = d')
            ->join(LsAssociation::class, 'a', 'WITH', 'a.destinationLsItem = i')
            ->join(LsItem::class, 'i2', 'WITH', 'a.originLsItem = i2')
            ->where('i2.lsDoc = :docId')
            ->setParameter('docId', $docId);
        $this->addAclConditions($qb, $user);
        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        foreach ($results as $row) {
            $id = (int) $row['id'];
            $foundIds[$id] = true;
            $docIds[] = $id;
        }
        $foundIds = array_unique($foundIds);
        $docIds = array_values(array_unique($docIds));

        // Query 2: Items where the document has an origin item
        // Original: SELECT DISTINCT i.ls_doc_id FROM ls_item i
        //   INNER JOIN ls_association a ON a.origin_lsitem_id = i.id
        //   INNER JOIN ls_item i2 ON i2.id = a.destination_lsitem_id
        //   WHERE i2.ls_doc_id = :docId
        $qb = $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsItem::class, 'i', 'WITH', 'i.lsDoc = d')
            ->join(LsAssociation::class, 'a', 'WITH', 'a.originLsItem = i')
            ->join(LsItem::class, 'i2', 'WITH', 'a.destinationLsItem = i2')
            ->where('i2.lsDoc = :docId')
            ->setParameter('docId', $docId);
        if (!empty($foundIds)) {
            $qb->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Framework\LsDoc d2 WHERE d2.id IN (:foundIds) AND d2.id = d.id)')
                ->setParameter('foundIds', array_keys($foundIds));
        }
        $this->addAclConditions($qb, $user);
        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        foreach ($results as $row) {
            $id = (int) $row['id'];
            $foundIds[$id] = true;
            $docIds[] = $id;
        }

        // Query 3: Items where association destination is the document
        // Original: SELECT DISTINCT i.ls_doc_id FROM ls_item i
        //   INNER JOIN ls_association a ON a.origin_lsitem_id = i.id
        //   WHERE a.destination_lsdoc_id = :docId
        $qb = $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsItem::class, 'i', 'WITH', 'i.lsDoc = d')
            ->join(LsAssociation::class, 'a', 'WITH', 'a.originLsItem = i')
            ->where('a.destinationLsDoc = :docId')
            ->setParameter('docId', $docId);
        if (!empty($foundIds)) {
            $qb->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Framework\LsDoc d2 WHERE d2.id IN (:foundIds) AND d2.id = d.id)')
                ->setParameter('foundIds', array_keys($foundIds));
        }
        $this->addAclConditions($qb, $user);
        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        foreach ($results as $row) {
            $id = (int) $row['id'];
            $foundIds[$id] = true;
            $docIds[] = $id;
        }

        // Query 4: Items where association origin is the document
        // Original: SELECT DISTINCT i.ls_doc_id FROM ls_item i
        //   INNER JOIN ls_association a ON a.destination_lsitem_id = i.id
        //   WHERE a.origin_lsdoc_id = :docId
        $qb = $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsItem::class, 'i', 'WITH', 'i.lsDoc = d')
            ->join(LsAssociation::class, 'a', 'WITH', 'a.destinationLsItem = i')
            ->where('a.originLsDoc = :docId')
            ->setParameter('docId', $docId);
        if (!empty($foundIds)) {
            $qb->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Framework\LsDoc d2 WHERE d2.id IN (:foundIds) AND d2.id = d.id)')
                ->setParameter('foundIds', array_keys($foundIds));
        }
        $this->addAclConditions($qb, $user);
        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        foreach ($results as $row) {
            $id = (int) $row['id'];
            $foundIds[$id] = true;
            $docIds[] = $id;
        }

        // Query 5: Document associations where destination is an item in the document
        // Original: SELECT DISTINCT a.ls_doc_id FROM ls_association a
        //   INNER JOIN ls_item i ON i.id = a.destination_lsitem_id
        //   WHERE i.ls_doc_id = :docId
        $qb = $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsAssociation::class, 'a', 'WITH', 'a.lsDoc = d')
            ->join(LsItem::class, 'i', 'WITH', 'a.destinationLsItem = i')
            ->where('i.lsDoc = :docId')
            ->setParameter('docId', $docId);
        if (!empty($foundIds)) {
            $qb->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Framework\LsDoc d2 WHERE d2.id IN (:foundIds) AND d2.id = d.id)')
                ->setParameter('foundIds', array_keys($foundIds));
        }
        $this->addAclConditions($qb, $user);
        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        foreach ($results as $row) {
            $id = (int) $row['id'];
            $foundIds[$id] = true;
            $docIds[] = $id;
        }

        // Query 6: Document associations where origin is an item in the document
        // Original: SELECT DISTINCT a.ls_doc_id FROM ls_association a
        //   INNER JOIN ls_item i ON i.id = a.origin_lsitem_id
        //   WHERE i.ls_doc_id = :docId
        $qb = $this->createQueryBuilder('d')
            ->select('DISTINCT d.id')
            ->join(LsAssociation::class, 'a', 'WITH', 'a.lsDoc = d')
            ->join(LsItem::class, 'i', 'WITH', 'a.originLsItem = i')
            ->where('i.lsDoc = :docId')
            ->setParameter('docId', $docId);
        if (!empty($foundIds)) {
            $qb->andWhere('NOT EXISTS (SELECT 1 FROM App\Entity\Framework\LsDoc d2 WHERE d2.id IN (:foundIds) AND d2.id = d.id)')
                ->setParameter('foundIds', array_keys($foundIds));
        }
        $this->addAclConditions($qb, $user);
        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        foreach ($results as $row) {
            $id = (int) $row['id'];
            $foundIds[$id] = true;
            $docIds[] = $id;
        }
        $foundIds = array_unique($foundIds);
        $docIds = array_values(array_unique($docIds));

        if (empty($docIds)) {
            return [];
        }

        // Fetch full entities for the filtered document IDs
        $qb = $this->createQueryBuilder('d')
            ->select('d, s')
            ->leftJoin('d.subjects', 's')
            ->where('d.id IN (:docIds)')
            ->setParameter('docIds', $docIds)
            ->orderBy('d.creator', 'ASC')
            ->addOrderBy('d.title', 'ASC')
            ->addOrderBy('d.adoptionStatus', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Add ACL conditions to a QueryBuilder for filtering documents by user permissions.
     */
    private function addAclConditions(QueryBuilder $qb, ?User $user): void
    {
        // If user has FRAMEWORK_EDIT_ALL permission, they can see all documents
        if (null !== $user && $this->security->isGranted(Permission::FRAMEWORK_EDIT_ALL)) {
            return;
        }

        $qb->leftJoin('d.mirroredFramework', 'm');

        // Anonymous user - only public, non-private documents
        if (null === $user) {
            $qb->andWhere('(m.visible IS NULL OR m.visible = 1)')
                ->andWhere('d.adoptionStatus != :privateDraft')
                ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);

            return;
        }

        // Logged-in user with specific permissions
        $isEditor = $this->security->isGranted('ROLE_EDITOR');

        // Join ACLs table for user-based access checking
        $qb->leftJoin('d.docAcls', 'acls', 'WITH', 'acls.user = :user');

        // Match findForList() logic: three separate OR conditions
        // 1. Public non-private documents
        // 2. Editor can see visible mirrored frameworks
        // 3. Org/user/acl positive check with negative check applied to that group only
        $qb->orWhere('(m.visible IS NULL OR m.visible = 1) AND (d.adoptionStatus != :privateDraft)')
            ->orWhere('(m.visible IS NOT NULL AND 1 = :isEditor)')
            ->orWhere('(d.org = :org OR d.user = :user OR acls.access = 1) AND (acls.access IS NULL OR acls.access != 0)')
            ->setParameter('isEditor', $isEditor ? 1 : 0)
            ->setParameter('user', $user)
            ->setParameter('org', $user->getOrg())
            ->setParameter('privateDraft', LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);
    }
}
