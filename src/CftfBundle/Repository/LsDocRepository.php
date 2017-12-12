<?php

namespace CftfBundle\Repository;

use CftfBundle\Entity\CaseApiInterface;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use Doctrine\ORM\Query;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Util\Compare;

/**
 * LsDocRepository
 */
class LsDocRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Finds an object for the API by ['id'=>identifier, 'class'=>class]
     *
     * @param array $id
     *
     * @return CaseApiInterface
     *
     * @throws NotFoundHttpException
     */
    public function apiFindOneByClassIdentifier(array $id): CaseApiInterface
    {
        /** @var CaseApiInterface $obj */
        $obj = $this->_em->getRepository($id['class'])->findOneBy(['identifier' => $id['id']]);
        if (null === $obj) {
            throw new NotFoundHttpException(sprintf('%s object not found.', $id['class']));
        }

        return $obj;
    }

    /**
     * @param string $slug
     *
     * @return object|null|LsDoc
     */
    public function findOneBySlug($slug)
    {
        if (preg_match('/^\d+$/', $slug)) {
            return $this->find($slug);
        }

        return $this->findOneBy(['urlName' => $slug]);
    }

    /**
     * @param CfDocQuery|null $query
     *
     * @return array|LsDoc[]
     */
    public function findAllDocuments(?CfDocQuery $query): array
    {
        if (null === $query) {
            $query = new CfDocQuery();
        }

        return $this->findBy([], ['id' => 'asc'], $query->getLimit(), $query->getOffset());
    }

    /**
     * Get a list of all items for an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findAllChildrenArray(LsDoc $lsDoc)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, t, a, g, adi, add
            FROM CftfBundle:LSItem i INDEX BY i.id
            LEFT JOIN i.itemType t
            LEFT JOIN i.associations a WITH a.lsDoc = :lsDocId AND a.type = :childOfType
            LEFT JOIN a.group g
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE i.lsDoc = :lsDocId
            ORDER BY i.rank ASC, i.listEnumInSource ASC, i.humanCodingScheme,
                     adi.rank ASC, adi.listEnumInSource ASC, adi.humanCodingScheme
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);

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
                        //$results[$key]['parents'][$parent['id']][] = ['group' => $association['group']['title']];
                        $results[$key]['assoc'][$parent['id']] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => $association['group']['id'],
                        ];
                    } else {
                        //$results[$key]['parents'][$parent['id']][] = ['group' => $association['groupName']];
                        $results[$key]['assoc'][$parent['id']] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => '',
                        ];
                    }
                } elseif (!empty($association['destinationLsDoc'])) {
                    if (!empty($association['group'])) {
                        // $results[$key]['parents']['doc'][] = ['group' => $association['group']['title']];
                        $results[$key]['assoc']['doc'] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => $association['group']['id'],
                        ];
                    } else {
                        //$results[$key]['parents']['doc'][] = ['group' => $association['groupName']];
                        $results[$key]['assoc']['doc'] = [
                            'id' => $association['id'],
                            'sequenceNumber' => $association['sequenceNumber'],
                            'group' => '',
                        ];
                    }
                }
            }
        }

        $this->rankItems($results);

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
     *   - then by "humanCodingScheme"
     *
     * @param array $itemArray
     */
    private function rankItems(array &$itemArray)
    {
        Compare::sortArrayByFields($itemArray, ['rank', 'listEnumInSource', 'humanCodingScheme']);
    }

    /**
     * Get a list of ids for all items that have parents for an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsItem ids
     */
    public function findAllItemsWithParentsArray(LsDoc $lsDoc)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i.id
            FROM CftfBundle:LsItem i INDEX by i.id
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
     * Get a list of all items for an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findTopChildrenIds(LsDoc $lsDoc)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, a, add
            FROM CftfBundle:LSItem i INDEX BY i.id
            JOIN i.associations a WITH a.lsDoc = :lsDocId AND a.type = :childOfType
            JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE i.lsDoc = :lsDocId
            ORDER BY i.rank ASC, i.listEnumInSource ASC, i.humanCodingScheme ASC
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());
        $query->setParameter('childOfType', LsAssociation::CHILD_OF);

        $results = $query->getResult(Query::HYDRATE_ARRAY);

        $this->rankItems($results);

        return array_keys($results);
    }

    /**
     * Delete an LsDoc and all associated items and associations
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     * @param \Closure|null $progressCallback
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteDocument(LsDoc $lsDoc, \Closure $progressCallback = null): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $params = ['lsDocId' => $lsDoc->getId()];

        if (null === $progressCallback) {
            $progressCallback = function ($message = '') {
            };
        }

        $progressCallback('Deleting associations');
        $stmt = <<<'xENDx'
DELETE FROM ls_association
 WHERE ls_doc_id = :lsDocId
    OR origin_lsitem_id IN (
      SELECT i.id
        FROM ls_item i
       WHERE i.ls_doc_id = :lsDocId
    )
    OR destination_lsitem_id IN (
      SELECT i.id
        FROM ls_item i
       WHERE i.ls_doc_id = :lsDocId
    )
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Deleting association groups');
        $stmt = <<<'xENDx'
DELETE FROM ls_def_association_grouping
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Deleting items');
        $stmt = <<<'xENDx'
DELETE FROM ls_item
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Deleting document subjects');
        $stmt = <<<'xENDx'
DELETE FROM ls_doc_subject
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Deleting document import logs');
        $stmt = <<<'xENDx'
DELETE FROM import_logs
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Deleting acls');
        $stmt = <<<'xENDx'
DELETE FROM salt_user_doc_acl
 WHERE doc_id = :lsDocId
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Deleting document attributes');
        $stmt = <<<'xENDx'
DELETE FROM ls_doc_attribute
 WHERE ls_doc_id = :lsDocId
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Deleting document');
        $stmt = <<<'xENDx'
DELETE FROM ls_doc
 WHERE id = :lsDocId
;
xENDx;
        $conn->prepare($stmt)->execute($params);

        $progressCallback('Done');
    }

    public function makeDerivative(LsDoc $oldLsDoc): LsDoc
    {
        $em = $this->getEntityManager();
        $newLsDoc = new LsDoc();
        $newLsDoc->setTitle($oldLsDoc->getTitle().' - Derivated');
        $newLsDoc->setCreator($oldLsDoc->getCreator());
        $newLsDoc->setVersion($oldLsDoc->getVersion());
        $newLsDoc->setDescription($oldLsDoc->getDescription());
        $newLsDoc->setSubject($oldLsDoc->getSubject());
        $newLsDoc->setNote($oldLsDoc->getNote());
        $newLsDoc->setLanguage($oldLsDoc->getLanguage());
        $newLsDoc->setOrg($oldLsDoc->getOrg());
        $newLsDoc->setUser($oldLsDoc->getUser());
        foreach($oldLsDoc->getAssociationGroupings() as $assocGroup) {
            $assocGroup->duplicateToLsDoc($newLsDoc);
        }
        $newLsDoc->setLicence($oldLsDoc->getLicence());

        $em->persist($newLsDoc);

        return $newLsDoc;
    }

    /**
     * @param LsDoc $fromDoc
     * @param LsDoc $toDoc
     * @param \Closure|null $progressCallback
     */
    public function copyDocumentToItem(LsDoc $fromDoc, LsDoc $toDoc, \Closure $progressCallback = null)
    {
        $em = $this->getEntityManager();

        if (null === $progressCallback) {
            $progressCallback = function ($message = '') {
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
     * Get an array representing the entire CF package
     *
     * @param LsDoc $doc
     *
     * @return array
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
                'CFLicenses' => $this->findAllUsedLicences($doc, Query::HYDRATE_OBJECT),
                'CFItemTypes' => $this->findAllUsedItemTypes($doc, Query::HYDRATE_OBJECT),
                'CFAssociationGroupings' => $this->findAllUsedAssociationGroups($doc, Query::HYDRATE_OBJECT),
            ]
        ];

        $rubrics = $this->findAllUsedRubrics($doc, Query::HYDRATE_OBJECT);
        if (0 < count($rubrics)) {
            $pkg['CFRubrics'] = $rubrics;
        }

        return $pkg;
    }

    /**
     * Get a list of all items for an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findAllItems(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, t, a, adi, add
            FROM CftfBundle:LsItem i INDEX BY i.id
            LEFT JOIN i.itemType t
            LEFT JOIN i.associations a WITH a.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE i.lsDoc = :lsDocId
            ORDER BY i.rank ASC, i.listEnumInSource ASC, i.humanCodingScheme,
                     adi.rank ASC, adi.listEnumInSource ASC, adi.humanCodingScheme
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all item types used in a document
     *
     * @param LsDoc $lsDoc
     * @param int $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedItemTypes(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT t
            FROM CftfBundle:LsDefItemType t, CftfBundle:LsItem i
            WHERE i.lsDoc = :lsDocId
              AND i.itemType = t
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all associations for an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllAssociations(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT a, ag, adi, add
            FROM CftfBundle:LsAssociation a INDEX BY a.id
            LEFT JOIN a.group ag
            LEFT JOIN a.destinationLsItem adi WITH adi.lsDoc = :lsDocId
            LEFT JOIN a.destinationLsDoc add WITH add.id = :lsDocId
            WHERE a.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all association groups used in an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllUsedAssociationGroups(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT ag
            FROM CftfBundle:LsDefAssociationGrouping ag, CftfBundle:LsAssociation a 
            WHERE a.lsDoc = :lsDocId
              AND a.group = ag
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all concepts used in a document
     *
     * @param LsDoc $lsDoc
     * @param int $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedConcepts(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT c
            FROM CftfBundle:LsDefConcept c, CftfBundle:LsItem i
            WHERE i.lsDoc = :lsDocId
              AND c MEMBER OF i.concepts
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all licences used in a document
     *
     * @param LsDoc $lsDoc
     * @param int $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedLicences(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT l
            FROM CftfBundle:LsDefLicence l, CftfBundle:LsItem i, CftfBundle:LsDoc d
            WHERE (i.lsDoc = :lsDocId AND i.licence = l)
               OR (d.id = :lsDocId AND d.licence = l)
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all licences used in a document
     *
     * @param LsDoc $lsDoc
     * @param int $format
     *
     * @return array array of LsDefItemTypes
     */
    public function findAllUsedRubrics(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT r
            FROM CftfBundle:CfRubric r
            JOIN r.criteria c
            JOIN c.item i
            WHERE i.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all association groups used in an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllDocAssociationGroups(LsDoc $lsDoc, $format = Query::HYDRATE_OBJECT)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT ag
            FROM CftfBundle:LsDefAssociationGrouping ag
            WHERE ag.lsDoc = :lsDocId
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all associations for an LsDoc where the nodes are known items
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsAssociations hydrated as an array
     */
    public function findAllAssociationsForCapturedNodes(LsDoc $lsDoc)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT a, ag, adi, add, odi, odd
            FROM CftfBundle:LSAssociation a INDEX BY a.id
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

        $results = $query->getResult(Query::HYDRATE_ARRAY);

        return $results;
    }

    /**
     * @param LsDoc $lsDoc
     *
     * @return array
     */
    public function findAssociatedDocs(LsDoc $lsDoc): array
    {
        $docs = [];

        $qb = $this->createQueryBuilder('d');
        $qb->select('partial d.{id,identifier,uri,title}')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.destinationLsItem', 'i2')
            ->where('i2.lsDoc = :doc')
            ->setParameter('doc', $lsDoc)
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        $qb = $this->createQueryBuilder('d');
        $qb->select('partial d.{id,identifier,uri,title}')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.originLsItem', 'i2')
            ->where('i2.lsDoc = :doc')
            ->setParameter('doc', $lsDoc)
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        $qb = $this->createQueryBuilder('d');
        $qb->select('partial d.{id,identifier,uri,title}')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.destinationLsDoc', 'd2')
            ->where('d2.id = :doc')
            ->setParameter('doc', $lsDoc)
        ;
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        foreach ($results as $doc) {
            $docs[$doc['identifier']] = [
                'autoLoad' => 'true',
                'url' => $doc['uri'],
                'title' => $doc['title'],
            ];
        }

        $qb = $this->createQueryBuilder('d');
        $qb->select('partial d.{id,identifier,uri,title}')
            ->distinct()
            ->join('d.lsItems', 'i')
            ->join('i.associations', 'a')
            ->join('a.originLsDoc', 'd2')
            ->where('d2.id = :doc')
            ->setParameter('doc', $lsDoc)
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
     * Get a list of all items for an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findItemsForExportDoc(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT i, t
            FROM CftfBundle:LsItem i INDEX BY i.id
            LEFT JOIN i.itemType t
            WHERE i.lsDoc = :lsDocId
            ORDER BY i.rank ASC, i.listEnumInSource ASC, i.humanCodingScheme
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }

    /**
     * Get a list of all items for an LsDoc
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc
     *
     * @return array array of LsItems hydrated as an array
     */
    public function findAssociationsForExportDoc(LsDoc $lsDoc, $format = Query::HYDRATE_ARRAY)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT a, g, partial oi.{id,identifier,lsDocIdentifier}, partial di.{id,identifier,lsDocIdentifier}
            FROM CftfBundle:LsAssociation a INDEX BY a.id
            LEFT JOIN a.group g
            LEFT JOIN a.originLsItem oi
            LEFT JOIN a.destinationLsItem di
            WHERE a.lsDoc = :lsDocId
            ORDER BY a.sequenceNumber ASC
        ');
        $query->setParameter('lsDocId', $lsDoc->getId());

        $results = $query->getResult($format);

        return $results;
    }
}
