<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFPackageItem;
use App\DTO\CaseJson\Definitions;
use App\DTO\CaseJson\LinkURI;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsItemRepository;
use App\Service\LoggerTrait;
use App\Util\EducationLevelSet;
use Doctrine\ORM\EntityManagerInterface;

class ItemsTransformer
{
    use LoggerTrait;

    private Definitions $definitions;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param CFPackageItem[] $cfItems
     *
     * @return LsItem[]
     */
    public function transform(array $cfItems, LsDoc $doc, Definitions $definitions): array
    {
        $this->definitions = $definitions;

        $items = $this->findExistingItems($cfItems);

        foreach ($cfItems as $cfItem) {
            $item = $items[$cfItem->identifier->toString()] ?? $this->createItem($cfItem, $doc);
            $items[$cfItem->identifier->toString()] = $this->updateItem($item, $cfItem, $doc);
        }

        $this->removeUnknownItems($doc, array_keys($items));

        return $items;
    }

    /**
     * @param CFPackageItem[] $cfItems
     *
     * @return LsItem[]
     */
    private function findExistingItems(array $cfItems): array
    {
        /** @var LsItemRepository $repo */
        $repo = $this->em->getRepository(LsItem::class);

        $newIds = array_map(static fn (CFPackageItem $item) => $item->identifier->toString(), $cfItems);

        return $repo->findByIdentifiers($newIds);
    }

    /**
     * @param string[] $itemIdentifiers
     */
    private function removeUnknownItems(LsDoc $doc, array $itemIdentifiers): void
    {
        $docItems = $doc->getLsItems();

        foreach ($docItems as $item) {
            if (!in_array($item->getIdentifier(), $itemIdentifiers, true)) {
                $this->em->remove($item);
            }
        }
    }

    private function createItem(CFPackageItem $cfItem, LsDoc $doc): LsItem
    {
        $item = new LsItem($cfItem->identifier);
        $item->setLsDoc($doc);

        $this->em->persist($item);

        return $item;
    }

    private function updateItem(LsItem $item, CFPackageItem $cfItem, LsDoc $doc): LsItem
    {
        if ($item->getLsDoc()->getIdentifier() !== $doc->getIdentifier()) {
            $this->error(sprintf('Attempt to change the document from %s to %s of item %s', $item->getLsDoc()->getIdentifier(), $doc->getIdentifier(), $cfItem->identifier->toString()));

            throw new \UnexpectedValueException('Cannot change the document of an item');
        }

        $item->setUri($cfItem->uri);
        $item->setAbbreviatedStatement($cfItem->abbreviatedStatement);
        $item->setAlternativeLabel($cfItem->alternativeLabel);
        $item->setFullStatement($cfItem->fullStatement);
        $item->setHumanCodingScheme($cfItem->humanCodingScheme);
        $item->setLanguage($cfItem->language);
        $item->setChangedAt($cfItem->lastChangeDateTime);
        $item->setListEnumInSource($cfItem->listEnumeration);
        $item->setNotes($cfItem->notes);
        $item->setStatusStart($cfItem->statusStartDate);
        $item->setStatusEnd($cfItem->statusEndDate);

        $item->setConceptKeywordsArray($cfItem->conceptKeywords);
        $edLevels = EducationLevelSet::fromStringOrArray($cfItem->educationLevel);
        $item->setEducationalAlignment($edLevels->toString());
        $item->setItemTypeText($cfItem->cfItemType);

        $this->updateItemType($item, $cfItem->cfItemTypeURI);
        $this->updateConcepts($item, $cfItem->conceptKeywordsURI);
        $this->updateLicence($item, $cfItem->licenseURI);

        return $item;
    }

    private function updateItemType(LsItem $item, ?LinkURI $cfItemTypeLink): void
    {
        if (null === $cfItemTypeLink) {
            $item->setItemType(null);

            return;
        }

        $itemTypeId = $cfItemTypeLink->identifier->toString();
        $item->setItemType($this->definitions->itemTypes[$itemTypeId] ?? null);
    }

    private function updateConcepts(LsItem $item, ?LinkURI $cfConceptLink): void
    {
        if (null === $cfConceptLink) {
            $item->setConcepts(null);

            return;
        }

        $conceptId = $cfConceptLink->identifier->toString();
        $concept = $this->definitions->concepts[$conceptId] ?? null;
        $item->setConcepts((null !== $concept) ? [$concept] : null);
    }

    private function updateLicence(LsItem $item, ?LinkURI $licenceLink): void
    {
        if (null === $licenceLink) {
            $item->setLicence(null);

            return;
        }

        $licenceId = $licenceLink->identifier->toString();
        $item->setLicence($this->definitions->licences[$licenceId] ?? null);
    }
}
