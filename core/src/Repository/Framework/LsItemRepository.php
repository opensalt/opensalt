<?php

declare(strict_types=1);

namespace App\Repository\Framework;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\InstitutionMatch;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LsItem>
 *
 * @method LsItem|null findOneByIdentifier(string $identifier)
 */
class LsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LsItem::class);
    }

    /**
     * @return LsItem[]
     */
    public function findAllByIdentifierOrHumanCodingSchemeByValue(string $key): array
    {
        $qry = $this->createQueryBuilder('i');
        $qry->select('i')
            ->where($qry->expr()->orX(
                $qry->expr()->eq('i.humanCodingScheme', ':humanCodingScheme'),
                $qry->expr()->eq('i.identifier', ':identifier')
            ))
            ->setParameter('humanCodingScheme', $key)
            ->setParameter('identifier', $key)
        ;

        return $qry->getQuery()->getResult();
    }

    /**
     * @param string $lsDocId
     * @param string $key
     *
     * @return LsItem[]
     */
    public function findByAllIdentifierOrHumanCodingSchemeByLsDoc($lsDocId, $key): array
    {
        $qry = $this->createQueryBuilder('i');
        $qry->select('i')
            ->where($qry->expr()->orX(
                $qry->expr()->eq('i.humanCodingScheme', ':humanCodingScheme'),
                $qry->expr()->eq('i.identifier', ':identifier')
            ), 'i.lsDoc = :lsDocId')
            ->setParameter('humanCodingScheme', $key)
            ->setParameter('identifier', $key)
            ->setParameter('lsDocId', $lsDocId)
        ;

        return $qry->getQuery()->getResult();
    }

    /**
     * @return LsAssociation[]
     */
    public function findChildAssociations(LsItem $parent, LsItem $child): array
    {
        $associations = [];
        foreach ($child->getAssociations() as $association) {
            if (LsAssociation::CHILD_OF === $association->getType()
                && null !== $association->getDestinationLsItem()
                && $association->getDestinationLsItem()->getId() === $parent->getId()) {
                $associations[] = $association;
            }
        }

        return $associations;
    }

    public function removeAssociation(LsAssociation $association): void
    {
        $this->getEntityManager()->getRepository(LsAssociation::class)->removeAssociation($association);
    }

    public function removeChild(LsItem $parent, LsItem $child): void
    {
        $associations = $this->findChildAssociations($parent, $child);
        foreach ($associations as $association) {
            $this->removeAssociation($association);
        }
    }

    public function removeItemAndChildren(LsItem $lsItem): bool
    {
        $children = $lsItem->getChildren();
        foreach ($children as $child) {
            $this->removeItemAndChildren($child);
        }

        return $this->removeItem($lsItem);
    }

    public function removeItem(LsItem $lsItem): bool
    {
        $hasChildren = $lsItem->getChildren();
        if ($hasChildren->isEmpty()) {
            $this->getEntityManager()->getRepository(LsAssociation::class)->removeAllAssociations($lsItem);
            $this->getEntityManager()->remove($lsItem);

            return true;
        }

        return false;
    }

    /**
     * @return LsItem[]
     */
    public function findExactMatches(string $identifier, int $maxDepth = 5): array
    {
        $assocRepo = $this->getEntityManager()->getRepository(LsAssociation::class);

        $item = $this->findOneByIdentifier($identifier);
        if (null === $item) {
            return [];
        }

        /** @psalm-suppress InvalidArrayOffset */
        $matched = [$item->getId() => $item];
        $matchedCount = 0;
        $depth = 0;

        while (count($matched) !== $matchedCount && $depth < $maxDepth) {
            $matchedCount = count($matched);
            ++$depth;

            $fromCriteria = new Criteria();
            $fromCriteria->where(Criteria::expr()->in('originLsItem', array_keys($matched)));
            $fromCriteria->andWhere(Criteria::expr()->eq('type', LsAssociation::EXACT_MATCH_OF));
            $results = $assocRepo->matching($fromCriteria);
            foreach ($results as $assoc) {
                /** @var LsAssociation $assoc */
                $item = $assoc->getDestinationLsItem();
                if (null !== $item) {
                    $matched[$item->getId()] = $item;
                }
            }

            $toCriteria = new Criteria();
            $toCriteria->where(Criteria::expr()->in('destinationLsItem', array_keys($matched)));
            $toCriteria->andWhere(Criteria::expr()->eq('type', LsAssociation::EXACT_MATCH_OF));
            $results = $assocRepo->matching($toCriteria);
            foreach ($results as $assoc) {
                /** @var LsAssociation $assoc */
                $item = $assoc->getOriginLsItem();
                if (null !== $item) {
                    $matched[$item->getId()] = $item;
                }
            }
        }

        return $matched;
    }

    public function findIdentifiersByLsDoc(LsDoc $doc): array
    {
        $result = $this->createQueryBuilder('i')
            ->select('i.identifier')
            ->where('i.lsDoc = :doc')
            ->setParameter('doc', $doc->getId())
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'identifier');
    }

    /**
     * @param string[] $identifiers
     *
     * @return LsItem[]
     */
    public function findByIdentifiers(array $identifiers, LsDoc $doc): array
    {
        if ([] === $identifiers) {
            return [];
        }

        $qb = $this->createQueryBuilder('t', 't.identifier');
        $qb->where($qb->expr()->in('t.identifier', $identifiers))
            ->andWhere('t.lsDoc = :lsDocId')
            ->setParameter('lsDocId', $doc->getId())
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param list<string> $identifiers
     *
     * @return list<LsItem>
     */
    public function findByIdentifiersAnyDoc(array $identifiers): array
    {
        if ([] === $identifiers) {
            return [];
        }

        /** @var list<LsItem> $items */
        $items = $this->createQueryBuilder('i')
            ->where('i.identifier IN (:identifiers)')
            ->setParameter('identifiers', array_values(array_unique($identifiers)))
            ->getQuery()
            ->getResult();

        return $items;
    }

    public function findOneByIdentifierAndKind(string $identifier, LsItemKind $kind): ?LsItem
    {
        return $this->findOneBy([
            'identifier' => $identifier,
            'discriminator' => $kind->value,
        ]);
    }

    public function findOneInstitutionByIdentifiers(EntityIdentifiers $request): ?LsItem
    {
        return $this->findInstitutionMatchByIdentifiers($request)?->item;
    }

    public function findInstitutionMatchByIdentifiers(EntityIdentifiers $request): ?InstitutionMatch
    {
        foreach ($request->identifiers as $identifier) {
            $itemId = $this->findItemIdMatchingIdentifier(
                $identifier->type,
                $identifier->value,
                static fn (string $baseSql, array &$params, array &$types): string => $baseSql.' AND i.discriminator = :orgKind',
                ['orgKind' => LsItemKind::Organization->value],
                ['orgKind' => ParameterType::INTEGER],
            );
            if (null !== $itemId) {
                $item = $this->find($itemId);
                if (null === $item) {
                    return null;
                }

                return new InstitutionMatch($item, $identifier);
            }
        }

        return null;
    }

    /**
     * @return list<LsItem>
     */
    public function findByLsDocAndKind(LsDoc $doc, LsItemKind $kind): array
    {
        return $this->findBy([
            'lsDoc' => $doc,
            'discriminator' => $kind->value,
        ]);
    }

    public function itemMatchesIdentifier(LsItem $item, string $type, string $value): bool
    {
        $itemId = $item->getId();
        if (null === $itemId) {
            return false;
        }

        return null !== $this->findItemIdMatchingIdentifier(
            $type,
            $value,
            static fn (string $baseSql, array &$params, array &$types): string => $baseSql.' AND i.id = :itemId',
            ['itemId' => $itemId],
            ['itemId' => ParameterType::INTEGER],
        );
    }

    public function findOneInDocByIdentifierAndKind(
        LsDoc $doc,
        LsItemKind $kind,
        string $type,
        string $value,
    ): ?LsItem {
        $docId = $doc->getId();
        if (null === $docId) {
            return null;
        }

        $itemId = $this->findItemIdMatchingIdentifier(
            $type,
            $value,
            static fn (string $baseSql, array &$params, array &$types): string => $baseSql.' AND i.ls_doc_id = :docId AND i.discriminator = :kind',
            [
                'docId' => $docId,
                'kind' => $kind->value,
            ],
            [
                'docId' => ParameterType::INTEGER,
                'kind' => ParameterType::INTEGER,
            ],
        );

        if (null === $itemId) {
            return null;
        }

        return $this->find($itemId);
    }

    /**
     * @param callable(string, array<string, mixed>, array<string, int|string>): string $appendScope
     * @param array<string, mixed> $scopeParams
     * @param array<string, int|string> $scopeTypes
     */
    private function findItemIdMatchingIdentifier(
        string $type,
        string $value,
        callable $appendScope,
        array $scopeParams = [],
        array $scopeTypes = [],
    ): ?int {
        $params = $scopeParams;
        $types = $scopeTypes;

        $matchSql = $this->identifierMatchSql($type, $value, $params, $types);
        $sql = $appendScope('SELECT i.id FROM ls_item i WHERE ('.$matchSql.')', $params, $types).' LIMIT 1';

        $id = $this->getEntityManager()->getConnection()->fetchOne($sql, $params, $types);
        if (false === $id || null === $id) {
            return null;
        }

        return (int) $id;
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int|string> $types
     */
    private function identifierMatchSql(string $type, string $value, array &$params, array &$types): string
    {
        return match ($type) {
            'identifier' => $this->bindScalarMatch('i.identifier', 'identifierValue', $value, $params, $types),
            'uri' => $this->bindScalarMatch('i.uri', 'uriValue', $value, $params, $types),
            'courseCode' => $this->courseCodeMatchSql($value, $params, $types),
            default => $this->extensionMatchSql($type, $value, $params, $types),
        };
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int|string> $types
     */
    private function bindScalarMatch(
        string $column,
        string $paramName,
        string $value,
        array &$params,
        array &$types,
    ): string {
        $params[$paramName] = $value;
        $types[$paramName] = ParameterType::STRING;

        return $column.' = :'.$paramName;
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int|string> $types
     */
    private function courseCodeMatchSql(string $value, array &$params, array &$types): string
    {
        $params['courseCodeValue'] = $value;
        $params['normalizedCourseCode'] = preg_replace('/\s+/', '', $value) ?? '';
        $types['courseCodeValue'] = ParameterType::STRING;
        $types['normalizedCourseCode'] = ParameterType::STRING;

        $normalizedSql = "REPLACE(REPLACE(REPLACE(REPLACE(i.human_coding_scheme, ' ', ''), CHAR(9), ''), CHAR(10), ''), CHAR(13), '') = :normalizedCourseCode";

        if ($value === $params['normalizedCourseCode']) {
            return 'i.human_coding_scheme = :courseCodeValue';
        }

        return '(i.human_coding_scheme = :courseCodeValue OR '.$normalizedSql.')';
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int|string> $types
     */
    private function extensionMatchSql(string $type, string $value, array &$params, array &$types): string
    {
        $this->assertValidExtensionKey($type);

        $jsonPath = $this->extensionJsonPath($type);
        $pathLiteral = $this->extensionJsonPathLiteral($type);
        $suffix = substr(md5($type), 0, 12);

        $memberValueParam = 'memberValue_'.$suffix;
        $jsonPathParam = 'jsonPath_'.$suffix;

        $params[$memberValueParam] = $value;
        $params[$jsonPathParam] = $jsonPath;
        $types[$memberValueParam] = ParameterType::STRING;
        $types[$jsonPathParam] = ParameterType::STRING;

        // JSON_UNQUOTE(JSON_EXTRACT(...)) matches both JSON strings and numbers as text.
        return sprintf(
            '(JSON_UNQUOTE(JSON_EXTRACT(i.ext, :%1$s)) = :%2$s OR :%2$s MEMBER OF (i.ext->%3$s))',
            $jsonPathParam,
            $memberValueParam,
            $pathLiteral,
        );
    }

    private function assertValidExtensionKey(string $type): void
    {
        if (!preg_match('/^[a-zA-Z0-9._:-]+$/', $type)) {
            throw new \InvalidArgumentException(sprintf('Invalid extension key "%s".', $type));
        }
    }

    private function extensionJsonPath(string $type): string
    {
        return '$."'.str_replace('"', '\\"', $type).'"';
    }

    private function extensionJsonPathLiteral(string $type): string
    {
        return "'".$this->extensionJsonPath($type)."'";
    }
}
