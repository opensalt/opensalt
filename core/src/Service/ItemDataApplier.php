<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ItemType\ItemTypeInterface;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Applies editor request payload data to an LsItem entity.
 */
class ItemDataApplier
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly HtmlSanitizerInterface $htmlSanitizer,
    ) {
    }

    public function applyDataToItem(LsItem $lsItem, array $data, ?string $itemType): void
    {
        $this->applyItemExtensions($lsItem, $data);
        $this->applyItemLicence($lsItem, $data);
        $this->applyItemSubjects($lsItem, $data);
        $this->applyItemItemType($lsItem, $data);

        if ($this->applyItemDto($lsItem, $data, $itemType)) {
            return;
        }

        $this->applyItemScalarFields($lsItem, $data);
        $this->applyItemAdditionalFields($lsItem, $data);
    }

    private function applyItemExtensions(LsItem $lsItem, array $data): void
    {
        if (!isset($data['extensions']) || !is_array($data['extensions'])) {
            return;
        }

        // Clear existing extensions if extensions are provided, replacing them entirely
        $lsItem->setExtensions(null);

        foreach ($data['extensions'] as $key => $value) {
            $lsItem->setExtensionProperty((string) $key, $value);
        }
    }

    private function applyItemLicence(LsItem $lsItem, array $data): void
    {
        if (!array_key_exists('licence', $data)) {
            return;
        }

        if (null !== $data['licence'] && '' !== $data['licence']) {
            $field = is_numeric($data['licence']) ? 'id' : 'identifier';
            $licence = $this->managerRegistry->getRepository(LsDefLicence::class)
                ->findOneBy([$field => $data['licence']]);
            if (null !== $licence) {
                $lsItem->setLicence($licence);
            }
        } else {
            $lsItem->setLicence(null);
        }
    }

    private function applyItemSubjects(LsItem $lsItem, array $data): void
    {
        if (!array_key_exists('subjects', $data)) {
            return;
        }

        $subjects = [];
        if (is_array($data['subjects'])) {
            foreach ($data['subjects'] as $subjectValue) {
                $subject = $this->resolveSubject($subjectValue);
                if (null !== $subject) {
                    $subjects[] = $subject;
                }
            }
        }
        $lsItem->setSubjects($subjects);
    }

    private function resolveSubject(mixed $subjectValue): ?LsDefSubject
    {
        if (empty($subjectValue)) {
            return null;
        }

        if (str_starts_with((string) $subjectValue, '__')) {
            $cleanValue = substr((string) $subjectValue, 2);
            $newSubject = new LsDefSubject();
            $newSubject->setTitle($cleanValue);
            $newSubject->setHierarchyCode($cleanValue);
            $this->managerRegistry->getManager()->persist($newSubject);

            return $newSubject;
        }

        $field = is_numeric($subjectValue) ? 'id' : 'identifier';

        return $this->managerRegistry->getRepository(LsDefSubject::class)
            ->findOneBy([$field => $subjectValue]);
    }

    private function applyItemItemType(LsItem $lsItem, array $data): void
    {
        if (!array_key_exists('itemType', $data)) {
            return;
        }

        $itemTypeValue = $data['itemType'];
        if (empty($itemTypeValue)) {
            $lsItem->setItemType(null);
        } elseif (str_starts_with((string) $itemTypeValue, '__')) {
            $cleanValue = substr((string) $itemTypeValue, 2);
            $newType = new LsDefItemType();
            $newType->setCode($cleanValue);
            $newType->setTitle($cleanValue);
            $newType->setHierarchyCode($cleanValue);
            $this->managerRegistry->getManager()->persist($newType);
            $lsItem->setItemType($newType);
        } else {
            $field = is_numeric($itemTypeValue) ? 'id' : 'identifier';
            $existingType = $this->managerRegistry->getRepository(LsDefItemType::class)
                ->findOneBy([$field => $itemTypeValue]);
            if (null !== $existingType) {
                $lsItem->setItemType($existingType);
            }
        }
    }

    private function applyItemDto(LsItem $lsItem, array $data, ?string $itemType): bool
    {
        if (null === $itemType) {
            return false;
        }

        $kind = LsItemKind::tryFromName($itemType);
        $dtoClass = $kind->dto();
        if (LsItem::class === $dtoClass) {
            return false;
        }

        $dto = $dtoClass::fromItem($lsItem);

        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }

        if ($dto instanceof ItemTypeInterface) {
            $lsItem->setDiscriminator($dto::ITEM_TYPE_IDENTIFIER);
            $dto->applyToItem($lsItem, $this->htmlSanitizer);

            if (isset($data['notes'])) {
                $lsItem->setNotes($data['notes']);
            }

            return true;
        }

        return false;
    }

    private function applyItemScalarFields(LsItem $lsItem, array $data): void
    {
        if (isset($data['fullStatement'])) {
            $lsItem->setFullStatement($data['fullStatement']);
        }
        if (array_key_exists('abbreviatedStatement', $data)) {
            $lsItem->setAbbreviatedStatement($this->nullableString($data['abbreviatedStatement']));
        }
        if (array_key_exists('humanCodingScheme', $data)) {
            $lsItem->setHumanCodingScheme($this->nullableString($data['humanCodingScheme']));
        }
        if (array_key_exists('listEnumeration', $data)) {
            $lsItem->setListEnumInSource($this->nullableString($data['listEnumeration']));
        } elseif (array_key_exists('listEnumInSource', $data)) {
            $lsItem->setListEnumInSource($this->nullableString($data['listEnumInSource']));
        }
        if (array_key_exists('conceptKeywords', $data)) {
            $conceptKeywords = $data['conceptKeywords'];
            if (is_array($conceptKeywords)) {
                $conceptKeywords = implode(', ', $conceptKeywords);
            }
            $lsItem->setConceptKeywords($this->nullableString((string) $conceptKeywords));
        }
        if (array_key_exists('notes', $data)) {
            $lsItem->setNotes($this->nullableString($data['notes']));
        }
        if (array_key_exists('language', $data)) {
            $lsItem->setLanguage($this->nullableString($data['language']));
        }
        if (array_key_exists('educationalAlignment', $data)) {
            $lsItem->setEducationalAlignment($this->nullableString($this->flattenArrayValue($data['educationalAlignment'] ?? '')));
        } elseif (array_key_exists('educationLevel', $data)) {
            $lsItem->setEducationalAlignment($this->nullableString($this->flattenArrayValue($data['educationLevel'] ?? '')));
        }
    }

    private function flattenArrayValue(string|array $value): string
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return (string) $value;
    }

    private function applyItemAdditionalFields(LsItem $lsItem, array $data): void
    {
        if (!isset($data['additionalFields']) || !is_array($data['additionalFields'])) {
            return;
        }

        foreach ($data['additionalFields'] as $fieldName => $value) {
            $lsItem->setAdditionalField($fieldName, $value);
        }
    }
}
