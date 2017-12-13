<?php

namespace App\Service;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Entity\LsDefConcept;
use CftfBundle\Entity\LsDefGrade;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDefLicence;
use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FrameworkService
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry
     * @param TokenStorageInterface $tokenStorage
     * @param ValidatorInterface $validator
     */
    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage, ValidatorInterface $validator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $registry->getManager();
        $this->validator = $validator;
    }

    /**
     * @param LsDoc $doc
     *
     * @return LsDoc
     *
     * @throws \InvalidArgumentException
     */
    public function addDocument(LsDoc $doc): LsDoc
    {
        $this->em->persist($doc);

        // Determine the owner
        $user = $doc->getUser() ?? $this->getCurrentUser();
        if (null === $user) {
            $doc->setOwnedBy(null);

            return $doc;
        }

        // If the owner has already been set then return (such as for console commands)
        if (null !== $doc->getOrg()) {
            $doc->setUser(null);
            $doc->setOwnedBy('organization');

            return $doc;
        }

        // Set the user or org (default) based on what was stipulated
        if ('user' === $doc->getOwnedBy()) {
            $doc->setUser($user);
            $doc->setOrg(null);
        } else {
            $doc->setUser(null);
            $doc->setOrg($user->getOrg());
            $doc->setOwnedBy('organization');
        }

        return $doc;
    }

    /**
     * @param LsDoc $doc
     * @param \Closure|null $callback
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteFramework(LsDoc $doc, ?\Closure $callback = null): void
    {
        $this->em
            ->getRepository(LsDoc::class)
            ->deleteDocument($doc, $callback);
    }

    public function persistItem(LsItem $item): void
    {
        $this->em->persist($item);
    }

    public function deleteItem(LsItem $item): void
    {
        $this->em->getRepository(LsItem::class)->removeItem($item);
    }

    public function deleteItemWithChildren(LsItem $item): void
    {
        $this->em->getRepository(LsItem::class)->removeItemAndChildren($item);
    }

    public function persistAssociation(LsAssociation $association): void
    {
        $this->em->persist($association);
    }

    public function addExemplarToItem(LsItem $item, string $url): LsAssociation
    {
        $lsAssociation = new LsAssociation();
        $lsAssociation->setLsDoc($item->getLsDoc());
        $lsAssociation->setOriginLsItem($item);
        $lsAssociation->setType(LsAssociation::EXEMPLAR);
        $lsAssociation->setDestinationNodeUri($url);
        $lsAssociation->setDestinationNodeIdentifier(Uuid::uuid5(Uuid::NAMESPACE_URL, $url));

        // TODO: setDestinationTitle is not currently a table field.
        //$lsAssociation->setDestinationTitle($request->request->get("exemplarDescription"));

        $this->em->persist($lsAssociation);

        return $lsAssociation;
    }

    public function addTreeAssociation(LsDoc $doc, array $origin, string $type, array $dest, ?string $assocGroup = null): LsAssociation
    {
        $association = new LsAssociation();
        $association->setType($type);
        $association->setLsDoc($doc);

        // deal with origin and dest items, which can be specified by id or by identifier
        // if externalDoc is specified for either one, mark this document as "autoLoad": "true" in the doc's externalDocuments
        $itemRepo = $this->em->getRepository(LsItem::class);

        if (!empty($origin['id'])) {
            $originItem = $itemRepo->findOneBy(['id'=>$origin['id']]);
            if (null === $originItem) {
                throw new \InvalidArgumentException('origin id is not a valid id');
            }
        } else {
            if (!empty($origin['externalDoc'])) {
                $doc->setExternalDocAutoLoad($origin['externalDoc'], 'true');
                $this->em->persist($doc);
            }
            $originItem = $origin['identifier'];
        }
        $association->setOrigin($originItem);

        if (!empty($dest['id'])) {
            $destItem = $itemRepo->findOneBy(['id'=>$dest['id']]);
            if (null === $destItem) {
                throw new \InvalidArgumentException('destination id is not a valid id');
            }
        } else {
            if (!empty($dest['externalDoc'])) {
                $doc->setExternalDocAutoLoad($dest['externalDoc'], 'true');
                $this->em->persist($doc);
            }
            $destItem = $dest['identifier'];
        }
        $association->setDestination($destItem);

        // set assocGroup if provided
        if (null !== $assocGroup) {
            $assocGroupRepo = $this->em->getRepository(LsDefAssociationGrouping::class);
            $assocGroupObj = $assocGroupRepo->findOneBy(['id'=>$assocGroup]);
            $association->setGroup($assocGroupObj);
        }

        $this->em->persist($association);

        return $association;
    }

    public function deleteAssociation(LsAssociation $association): void
    {
        $this->em->remove($association);
    }

    public function updateTreeItems(LsDoc $doc, array $items): array
    {
        $rv = [];

        foreach ($items as $lsItemId => $updates) {
            $this->updateTreeItem($doc, $lsItemId, $updates, $rv);
        }

        return $rv;
    }

    public function updateTreeItem(LsDoc $doc, string $itemId, array $updates, array &$rv): void
    {
        $assocGroupRepo = $this->em->getRepository(LsDefAssociationGrouping::class);

        // set assocGroup if supplied; pass this in when necessary below
        $assocGroup = null;
        if (array_key_exists('assocGroup', $updates)) {
            $assocGroup = $assocGroupRepo->find($updates['assocGroup']);
        }

        $lsItem = $this->getTreeItemForUpdate($doc, $updates, $itemId, $assocGroup);

        if (null === $lsItem) {
            return;
        }

        // return the id and fullStatement of the item, whether it's new or it already existed
        $rv[$itemId] = [
            'originalKey' => $updates['originalKey'],
            'lsItem' => $lsItem,
            'lsItemIdentifier' => $lsItem->getIdentifier(),
            'fullStatement' => $lsItem->getFullStatement(),
        ];

        if (array_key_exists('deleteChildOf', $updates)) {
            $this->deleteTreeChildAssociations($lsItem, $updates, $itemId, $rv);
        } elseif (array_key_exists('updateChildOf', $updates)) {
            $this->updateTreeChildOfAssociations($lsItem, $updates, $itemId, $rv);
        }

        // create new childOf association if specified
        if (array_key_exists('newChildOf', $updates)) {
            $this->addTreeChildOfAssociations($lsItem, $updates, $itemId, $rv, $assocGroup);
        }

        $lsItem->setUpdatedAt(new \DateTime());
    }

    public function persistAssociationGroup(LsDefAssociationGrouping $associationGroup): void
    {
        $this->em->persist($associationGroup);
    }

    public function deleteAssociationGroup(LsDefAssociationGrouping $associationGroup): void
    {
        $this->em->remove($associationGroup);
    }

    public function persistConcept(LsDefConcept $concept): void
    {
        $this->em->persist($concept);
    }

    public function deleteConcept(LsDefConcept $concept): void
    {
        $this->em->remove($concept);
    }

    public function persistGrade(LsDefGrade $grade): void
    {
        $this->em->persist($grade);
    }

    public function deleteGrade(LsDefGrade $grade): void
    {
        $this->em->remove($grade);
    }

    public function persistItemType(LsDefItemType $itemType): void
    {
        $this->em->persist($itemType);
    }

    public function deleteItemType(LsDefItemType $itemType): void
    {
        $this->em->remove($itemType);
    }

    public function persistLicence(LsDefLicence $licence): void
    {
        $this->em->persist($licence);
    }

    public function deleteLicence(LsDefLicence $licence): void
    {
        $this->em->remove($licence);
    }

    public function persistSubject(LsDefSubject $subject): void
    {
        $this->em->persist($subject);
    }

    public function deleteSubject(LsDefSubject $subject): void
    {
        $this->em->remove($subject);
    }

    /**
     * Get the item to update, either the original or a copy based on the update array
     *
     * @param LsDoc $lsDoc
     * @param array $updates
     * @param int $lsItemId
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @return LsItem|null
     *
     * @throws \UnexpectedValueException
     */
    protected function getTreeItemForUpdate(LsDoc $lsDoc, array $updates, $lsItemId, ?LsDefAssociationGrouping $assocGroup = null): ?LsItem
    {
        $lsItemRepo = $this->em->getRepository(LsItem::class);

        if (!array_key_exists('copyFromId', $updates)) {
            return $lsItemRepo->find($lsItemId);
        }

        // copy item if copyFromId is specified
        $originalItem = $lsItemRepo->find($updates['copyFromId']);

        if (null === $originalItem) {
            return null;
        }

        /** @var LsItem $lsItem */
        $lsItem = $originalItem->copyToLsDoc($lsDoc, $assocGroup);

        // if addCopyToTitle is set, add "Copy of " to fullStatement and abbreviatedStatement
        if (array_key_exists('addCopyToTitle', $updates)) {
            $title = 'Copy of '.$lsItem->getFullStatement();
            $lsItem->setFullStatement($title);

            $abbreviatedStatement = $lsItem->getAbbreviatedStatement();
            if (null !== $abbreviatedStatement) {
                $abbreviatedStatement = 'Copy of '.$abbreviatedStatement;
                $lsItem->setAbbreviatedStatement($abbreviatedStatement);
            }
        }

        $this->em->persist($lsItem);

        return $lsItem;
    }

    /**
     * Remove the appropriate childOf associations for the item based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function deleteTreeChildAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv): void
    {
        $assocRepo = $this->em->getRepository(LsAssociation::class);

        // delete childOf association if specified
        if ($updates['deleteChildOf']['assocId'] !== 'all') {
            $assocRepo->removeAssociation($assocRepo->find($updates['deleteChildOf']['assocId']));
            $rv[$lsItemId]['deleteChildOf'] = $updates['deleteChildOf']['assocId'];
        } else {
            // if we got "all" for the assocId, it means that we're updating a new item for which the client didn't know an assocId.
            // so in this case, it's OK to just delete any existing childof association and create a new one below
            $assocRepo->removeAllAssociationsOfType($lsItem, LsAssociation::CHILD_OF);
        }
    }

    /**
     * Update the childOf associations based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     */
    protected function updateTreeChildOfAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv): void
    {
        $assocRepo = $this->em->getRepository(LsAssociation::class);

        // update childOf association if specified
        $assoc = $assocRepo->find($updates['updateChildOf']['assocId']);
        if (null === $assoc) {
            return;
        }

        // as of now the only thing we update is sequenceNumber
        if (array_key_exists('sequenceNumber', $updates['updateChildOf'])) {
            $assoc->setSequenceNumber($updates['updateChildOf']['sequenceNumber']*1);
        }
        $rv[$lsItemId]['association'] = $assoc;
        $rv[$lsItemId]['sequenceNumber'] = $updates['updateChildOf']['sequenceNumber'];
    }

    /**
     * Add new childOf associations based on the update array
     *
     * @param LsItem $lsItem
     * @param array $updates
     * @param int $lsItemId
     * @param array $rv
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @throws \UnexpectedValueException
     */
    protected function addTreeChildOfAssociations(LsItem $lsItem, array $updates, $lsItemId, array &$rv, ?LsDefAssociationGrouping $assocGroup = null): void
    {
        // parent could be a doc or item
        if ($updates['newChildOf']['parentType'] === 'item') {
            $lsItemRepo = $this->em->getRepository(LsItem::class);
            $parentItem = $lsItemRepo->find($updates['newChildOf']['parentId']);
        } else {
            $docRepo = $this->em->getRepository(LsDoc::class);
            $parentItem = $docRepo->find($updates['newChildOf']['parentId']);
        }

        $rv[$lsItemId]['association'] = $lsItem->addParent($parentItem, $updates['newChildOf']['sequenceNumber'], $assocGroup);
        $rv[$lsItemId]['sequenceNumber'] = $updates['newChildOf']['sequenceNumber'];
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @see TokenInterface::getUser()
     */
    protected function getCurrentUser(): ?User
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
}
