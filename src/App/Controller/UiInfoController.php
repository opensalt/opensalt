<?php

namespace App\Controller;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/treeuiinfo")
 */
class UiInfoController extends AbstractController
{
    /**
     * @Route("/multi/{id}", name="multi_tree_info_json")
     * @Method({"POST"})
     * @Security("is_granted('edit', doc)")
     *
     * @return JsonResponse
     */
    public function multiJsonInfoAction(Request $request, LsDoc $doc): JsonResponse
    {
        $objs = [];
        if ($request->request->has('doc') && is_array($request->request->has('doc'))) {
            $objs['docs'] = [];
            foreach ($request->request->get('doc') as $id) {
                $d = $this->getDoctrine()->getRepository(LsDoc::class)
                    ->find($id);
                if (null !== $d) {
                    $objs['docs'][$id] = $this->generateDocArray($d);
                }
            }
        }

        if ($request->request->has('item')) {
            foreach ($request->request->get('item') as $id) {
                $i = $this->getDoctrine()->getRepository(LsItem::class)
                    ->find($id);
                if (null !== $i) {
                    $objs['items'][$id] = $this->generateItemArray($i);
                }
            }
        }

        if ($request->request->has('assoc')) {
            foreach ($request->request->get('assoc') as $id) {
                $a = $this->getDoctrine()->getRepository(LsAssociation::class)
                    ->find($id);
                if (null !== $a) {
                    $objs['assocs'][$id] = $this->generateAssociationArray($a);
                }
            }
        }

        return new JsonResponse($objs);
    }

    /**
     * @Route("/doc/{id}", name="lsdoc_tree_json")
     * @Method({"GET"})
     * @Security("is_granted('edit', doc)")
     *
     * @param LsDoc $doc
     *
     * @return JsonResponse
     */
    public function docJsonInfoAction(LsDoc $doc): JsonResponse
    {
        return $this->generateDocJsonResponse($doc);
    }

    /**
     * @Route("/item/{id}", name="lsitem_tree_json")
     * @Method({"GET"})
     * @Security("is_granted('edit', item)")
     *
     * @param LsItem $item
     *
     * @return JsonResponse
     */
    public function itemJsonInfoAction(LsItem $item): JsonResponse
    {
        return $this->generateItemJsonResponse($item);
    }

    /**
     * @Route("/association/{id}", name="doc_tree_association_json")
     * @Method({"GET"})
     * @Security("is_granted('edit', association.getLsDoc())")
     */
    public function associationJsonInfoAction(LsAssociation $association): JsonResponse
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
            'title' => $doc->getTitle(),
            'officialSourceURL' => $doc->getOfficialUri(),
            'creator' => $doc->getCreator(),
            'publisher' => $doc->getPublisher(),
            'description' => $doc->getDescription(),
            'language' => $doc->getLanguage(),
            'adoptionStatus' => $doc->getAdoptionStatus(),
            'statusStart' => (null !== $doc->getStatusStart()) ? $doc->getStatusStart()->format('Y-m-d') : null,
            'statusEnd' => (null !== $doc->getStatusEnd()) ? $doc->getStatusEnd()->format('Y-m-d') : null,
            'note' => $doc->getNote(),
            'version' => $doc->getVersion(),
            'lastChangeDateTime' => $doc->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }

    protected function generateItemJsonResponse(LsItem $item): JsonResponse
    {
        return new JsonResponse($this->generateItemArray($item));
    }

    protected function generateItemArray(LsItem $item): array
    {
        // retrieve isChildOf assoc id for the item
        /** @var LsAssociation $assoc */
        $assoc = $this->getDoctrine()->getRepository(LsAssociation::class)->findOneBy([
            'originLsItem' => $item,
            'type' => LsAssociation::CHILD_OF,
            'lsDoc' => $item->getLsDoc(),
        ]);

        $ret = [
            'id' => $item->getId(),
            'identifier' => $item->getIdentifier(),
            'fullStatement' => $item->getFullStatement(),
            'humanCodingScheme' => $item->getHumanCodingScheme(),
            'listEnumInSource' => $item->getListEnumInSource(),
            'abbreviatedStatement' => $item->getAbbreviatedStatement(),
            'conceptKeywords' => $item->getConceptKeywords(),
            'conceptKeywordsUri' => $item->getConceptKeywordsUri(),
            'notes' => $item->getNotes(),
            'language' => $item->getLanguage(),
            'educationalAlignment' => $item->getEducationalAlignment(),
            'itemType' => $item->getItemType(),
            'changedAt' => $item->getChangedAt(),
            'extra' => [],
        ];

        if (null !== $assoc) {
            $destItem = $assoc->getDestinationNodeIdentifier();

            if (null !== $destItem) {
                $ret['extra'] = [
                    'assocDoc' => $assoc->getLsDocIdentifier(),
                    'assocId' => $assoc->getId(),
                    'identifier' => $assoc->getIdentifier(),
                    //'groupId' => (null !== $assoc->getGroup()) ? $assoc->getGroup()->getId() : null,
                    'dest' => ['doc' => $assoc->getLsDocIdentifier(), 'item' => $destItem, 'uri' => $destItem],
                ];
                if ($assoc->getGroup()) {
                    $ret['extra']['groupId'] = $assoc->getGroup()->getId();
                }
                if ($assoc->getSequenceNumber()) {
                    $ret['extra']['seq'] = $assoc->getSequenceNumber();
                }
            }
        }

        $json = $this->renderView('CftfBundle:DocTree:export_item.json.twig', ['lsItem' => $ret]);
        return \json_decode($json, true);
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
            'groupId' => $association->getGroup() ? $association->getGroup()->getId() : null,
            'seq' => $association->getSequenceNumber(),
            'mod' => $association->getUpdatedAt()->format('Y-m-d\TH:i:s'),
        ];
    }
}
