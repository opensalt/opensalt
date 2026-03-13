<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddItemCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Command\Framework\UpdateItemCommand;
use App\DTO\ItemType\ItemTypeInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Repository\Framework\LsDocRepository;
use App\Repository\Framework\LsItemRepository;
use App\Security\Permission;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/framework/editor')]
class ItemController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly LsDocRepository $docRepository,
        private readonly LsItemRepository $itemRepository,
    ) {
    }

    #[Route(path: '/item/new/{parentIdentifier}', name: 'editor_item_new', methods: ['POST'])]
    public function newItem(
        Request $request,
        string $parentIdentifier,
    ): Response {
        $parentItem = $this->itemRepository->findOneBy(['identifier' => $parentIdentifier]);
        $doc = null;
        $parent = null;

        if (null !== $parentItem) {
            $parent = $parentItem;
            $doc = $parent->getLsDoc();
        } else {
            $doc = $this->docRepository->findOneBy(['identifier' => $parentIdentifier]);
        }

        if (null === $doc) {
            return new JsonResponse(['error' => 'Parent framework or item not found.'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted(Permission::ITEM_ADD_TO, $doc)) {
            return new JsonResponse(['error' => 'Access Denied.'], Response::HTTP_FORBIDDEN);
        }

        $lsItem = new LsItem();
        $lsItem->setLsDoc($doc);
        $lsItem->setLsDocUri($doc->getUri());

        // Parse request body
        $data = json_decode($request->getContent(), true);

        // Extract itemType from extensions.salt:type, fall back to query parameter
        $itemType = $data['extensions']['salt:type'] ?? $request->query->get('itemType');

        if (null !== $data) {
            $this->applyDataToItem($lsItem, $data, $itemType);
        }

        try {
            $command = new AddItemCommand($lsItem, $doc, $parent);
            $this->sendCommand($command);

            /** @var ?LsAssociation $assoc */
            $assoc = $this->managerRegistry->getRepository(LsAssociation::class)->findOneBy(['originLsItem' => $lsItem, 'type' => LsAssociation::CHILD_OF]);

            return $this->generateItemJsonResponse($lsItem, $assoc);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/item/{identifier}', name: 'editor_item_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(Permission::ITEM_EDIT, 'lsItem')]
    public function updateItem(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $lsItem,
    ): Response {
        // Parse request body
        $data = json_decode($request->getContent(), true);

        // Extract itemType from extensions.salt:type, fall back to query parameter
        $itemType = $data['extensions']['salt:type'] ?? $request->query->get('itemType');

        if (null !== $data) {
            $this->applyDataToItem($lsItem, $data, $itemType);
        }

        try {
            $command = new UpdateItemCommand($lsItem);
            $this->sendCommand($command);

            return $this->generateItemJsonResponse($lsItem);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/item/{identifier}', name: 'editor_item_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::ITEM_EDIT, 'lsItem')]
    public function deleteItem(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsItem $lsItem,
        #[MapQueryParameter] int $includingChildren = 0,
    ): Response {
        try {
            if (0 === $includingChildren) {
                $command = new DeleteItemCommand($lsItem);
            } else {
                $command = new DeleteItemWithChildrenCommand($lsItem);
            }
            $this->sendCommand($command);

            return new JsonResponse(['status' => 'OK'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function applyDataToItem(LsItem $lsItem, array $data, ?string $itemType): void
    {
        // If itemType is provided, try to use specialized DTO
        if (null !== $itemType) {
            $kind = LsItemKind::tryFromName($itemType);
            if (null !== $kind) {
                $dtoClass = $kind->dto();
                if (LsItem::class !== $dtoClass) {
                    $dto = $dtoClass::fromItem($lsItem);
                    // Use simple property mapping for now, or mimic form handling
                    // For now, let's just use applyToItem if we can populate the DTO
                    // This is a bit complex without the full form system,
                    // but we can manually set properties on the DTO.

                    // Simple property mapper for the DTO
                    foreach ($data as $key => $value) {
                        if (property_exists($dto, $key)) {
                            $dto->$key = $value;
                        }
                    }

                    if ($dto instanceof ItemTypeInterface) {
                        $lsItem->setDiscriminator($dto::ITEM_TYPE_IDENTIFIER);
                        $dto->applyToItem($lsItem, $this->htmlSanitizer);

                        return;
                    }
                }
            }
        }

        // Default item handling
        if (isset($data['fullStatement'])) {
            $lsItem->setFullStatement($data['fullStatement']);
        }
        if (isset($data['abbreviatedStatement'])) {
            $lsItem->setAbbreviatedStatement($data['abbreviatedStatement']);
        }
        if (isset($data['humanCodingScheme'])) {
            $lsItem->setHumanCodingScheme($data['humanCodingScheme']);
        }
        if (isset($data['listEnumeration'])) {
            $lsItem->setListEnumInSource($data['listEnumeration']);
        }
        if (isset($data['conceptKeywords'])) {
            $conceptKeywords = $data['conceptKeywords'];
            if (is_array($conceptKeywords)) {
                $conceptKeywords = implode(', ', $conceptKeywords);
            }
            $lsItem->setConceptKeywords($conceptKeywords);
        }
        if (isset($data['notes'])) {
            $lsItem->setNotes($data['notes']);
        }
        if (isset($data['language'])) {
            $lsItem->setLanguage($data['language']);
        }
        if (isset($data['educationalAlignment'])) {
            $educationalAlignment = $data['educationalAlignment'];
            if (is_array($educationalAlignment)) {
                $educationalAlignment = implode(', ', $educationalAlignment);
            }
            $lsItem->setEducationalAlignment($educationalAlignment);
        }
        // ... add more as needed
    }

    private function generateItemJsonResponse(LsItem $item, ?LsAssociation $assoc = null): Response
    {
        // Mimic LsItemController::generateItemJsonResponse
        $ret = [
            'id' => $item->getId(),
            'identifier' => $item->getIdentifier(),
            'uri' => $item->getUri(),
            'objectType' => $item->getObjectType(),
            'fullStatement' => $item->getFullStatement(),
            'humanCodingScheme' => $item->getHumanCodingScheme(),
            'listEnumInSource' => $item->getListEnumInSource(),
            'abbreviatedStatement' => $item->getAbbreviatedStatement(),
            'conceptKeywords' => $item->getConceptKeywordsString(),
            'conceptKeywordsUri' => $item->getConceptKeywordsUri(),
            'notes' => $item->getNotes(),
            'language' => $item->getLanguage(),
            'educationalAlignment' => $item->getEducationalAlignment(),
            'itemType' => $item->getItemType(),
            'changedAt' => $item->getChangedAt(),
            'extra' => $item->getExtra(),
            'assocData' => [],
        ];

        if (null !== $assoc) {
            $destItem = $assoc->getDestinationNodeIdentifier();

            if (null !== $destItem) {
                $ret['assocData'] = [
                    'assocDoc' => $assoc->getLsDocIdentifier(),
                    'assocId' => $assoc->getId(),
                    'identifier' => $assoc->getIdentifier(),
                    'dest' => ['doc' => $assoc->getLsDocIdentifier(), 'item' => $destItem, 'uri' => $destItem],
                ];
                if (null !== $assoc->getGroup()) {
                    $ret['assocData']['groupId'] = $assoc->getGroup()->getId();
                    $ret['assocData']['groupIdentifier'] = $assoc->getGroup()->getIdentifier();
                }
                if (!in_array($assoc->getSequenceNumber(), [null, 0], true)) {
                    $ret['assocData']['seq'] = $assoc->getSequenceNumber();
                }
            }
        }

        return new JsonResponse($ret);
    }
}
