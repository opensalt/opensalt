<?php

namespace App\Controller;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Security\Permission;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/treeuiinfo')]
class UiInfoController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    #[Route(path: '/multi/{id}', name: 'multi_tree_info_json', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    public function multiJsonInfo(Request $request, LsDoc $doc): JsonResponse
    {
        $objs = [];

        /** @var array $docs - argument passed as an array */
        $docs = $request->request->all('doc');
        foreach ($docs as $id) {
            $d = $this->managerRegistry->getRepository(LsDoc::class)
                ->find($id);
            if (null !== $d) {
                $objs['docs'][$id] = $this->generateDocArray($d);
            }
        }

        /** @var array $items - argument passed as an array */
        $items = $request->request->all('item');
        foreach ($items as $id) {
            $i = $this->managerRegistry->getRepository(LsItem::class)
                ->find($id);
            if (null !== $i) {
                $objs['items'][$id] = $this->generateItemArray($i);
            }
        }

        /** @var array $assocs - argument passed as an array */
        $assocs = $request->request->all('assoc');
        foreach ($assocs as $id) {
            $a = $this->managerRegistry->getRepository(LsAssociation::class)
                ->find($id);
            if (null !== $a) {
                $objs['assocs'][$id] = $this->generateAssociationArray($a);
            }
        }

        return new JsonResponse($objs);
    }

    #[Route(path: '/doc/{id}', name: 'lsdoc_tree_json', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'doc')]
    public function docJsonInfo(LsDoc $doc): JsonResponse
    {
        return $this->generateDocJsonResponse($doc);
    }

    #[Route(path: '/item/{id}', name: 'lsitem_tree_json', methods: ['GET'])]
    #[IsGranted(Permission::ITEM_EDIT, 'item')]
    public function itemJsonInfo(LsItem $item): JsonResponse
    {
        return $this->generateItemJsonResponse($item);
    }

    #[Route(path: '/association/{id}', name: 'doc_tree_association_json', methods: ['GET'])]
    #[IsGranted(Permission::ASSOCIATION_EDIT, new Expression('args["association"].getLsDoc()'))]
    public function associationJsonInfo(LsAssociation $association): JsonResponse
    {
        return $this->generateAssociationJsonResponse($association);
    }

    protected function generateDocJsonResponse(LsDoc $doc): JsonResponse
    {
        return new JsonResponse($this->generateDocArray($doc));
    }

    protected function generateDocArray(LsDoc $doc): array
    {
        return [
            'id' => $doc->getId(),
            'identifier' => $doc->getIdentifier(),
            'uri' => $doc->getUri(),
            'title' => $doc->getTitle(),
            'officialSourceURL' => $doc->getOfficialUri(),
            'creator' => $doc->getCreator(),
            'publisher' => $doc->getPublisher(),
            'description' => $doc->getDescription(),
            'language' => $doc->getLanguage(),
            'adoptionStatus' => $doc->getAdoptionStatus(),
            'statusStart' => $doc->getStatusStart()?->format('Y-m-d'),
            'statusEnd' => $doc->getStatusEnd()?->format('Y-m-d'),
            'note' => $doc->getNote(),
            'version' => $doc->getVersion(),
            'lastChangeDateTime' => $doc->getChangedAt()->format('Y-m-d\TH:i:s'),
        ];
    }

    protected function generateItemJsonResponse(LsItem $item): JsonResponse
    {
        return new JsonResponse($this->generateItemArray($item));
    }

    protected function generateItemArray(LsItem $item): array
    {
        // retrieve isChildOf assoc id for the item
        $assoc = $this->managerRegistry->getRepository(LsAssociation::class)->findOneBy([
            'originLsItem' => $item,
            'type' => LsAssociation::CHILD_OF,
            'lsDoc' => $item->getLsDoc(),
        ]);

        $ret = [
            'id' => $item->getId(),
            'identifier' => $item->getIdentifier(),
            'uri' => $item->getUri(),
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
            'assocData' => [],
            'extra' => $item->getExtra(),
        ];

        if (null !== $assoc) {
            $destItem = $assoc->getDestinationNodeIdentifier();

            if (null !== $destItem) {
                $ret['assocData'] = [
                    'assocDoc' => $assoc->getLsDocIdentifier(),
                    'assocId' => $assoc->getId(),
                    'identifier' => $assoc->getIdentifier(),
                    'uri' => $assoc->getUri(),
                    //'groupId' => $assoc->getGroup()?->getId(),
                    'dest' => ['doc' => $assoc->getLsDocIdentifier(), 'item' => $destItem, 'uri' => $destItem],
                ];
                if ($assoc->getGroup()) {
                    $ret['assocData']['groupId'] = $assoc->getGroup()->getId();
                }
                if ($assoc->getSequenceNumber()) {
                    $ret['assocData']['seq'] = $assoc->getSequenceNumber();
                }
            }
        }

        $json = $this->renderView('framework/doc_tree/export_item.json.twig', ['lsItem' => $ret]);

        return \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function generateAssociationJsonResponse(LsAssociation $association): JsonResponse
    {
        return new JsonResponse($this->generateAssociationArray($association));
    }

    protected function generateAssociationArray(LsAssociation $association): array
    {
        $origin = $association->getOrigin();
        if (\is_string($origin)) {
            $originIdentifier = preg_replace('/^local:/', '', $origin);
            $originDoc = $association->getLsDocIdentifier();
        } else {
            $originIdentifier = $origin->getIdentifier();
            if ($origin instanceof LsDoc) {
                $originDoc = $origin->getIdentifier();
            } else {
                $originDoc = $origin->getLsDocIdentifier();
            }
        }
        $dest = $association->getDestination();
        if (\is_string($dest)) {
            $destIdentifier = preg_replace('/^local:/', '', $dest);
            $destDoc = $association->getLsDocIdentifier();
        } else {
            $destIdentifier = $dest->getIdentifier();
            if ($dest instanceof LsDoc) {
                $destDoc = $dest->getIdentifier();
            } else {
                $destDoc = $dest->getLsDocIdentifier();
            }
        }

        return [
            'id' => $association->getId(),
            'identifier' => $association->getIdentifier(),
            'origin' => [
                'doc' => $originDoc,
                'item' => $originIdentifier,
                'uri' => $originIdentifier,
            ],
            'type' => $association->getNormalizedType(),
            'dest' => [
                'doc' => $destDoc,
                'item' => $destIdentifier,
                'uri' => $destIdentifier,
            ],
            'groupId' => $association->getGroup()?->getId(),
            'seq' => $association->getSequenceNumber(),
            'mod' => $association->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }
}
