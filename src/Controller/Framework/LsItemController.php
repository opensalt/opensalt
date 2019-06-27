<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddItemCommand;
use App\Command\Framework\ChangeItemParentCommand;
use App\Command\Framework\CopyItemToDocCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\LockItemCommand;
use App\Command\Framework\RemoveChildCommand;
use App\Command\Framework\UpdateItemCommand;
use App\Exception\AlreadyLockedException;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Form\Command\ChangeLsItemParentCommand;
use App\Form\Command\CopyToLsDocCommand;
use App\Form\Type\LsDocListType;
use App\Form\Type\LsItemParentType;
use App\Form\Type\LsItemType;
use App\Service\BucketService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LsItem controller.
 *
 * @Route("/cfitem")
 */
class LsItemController extends AbstractController
{
    use CommandDispatcherTrait;

    private $bucketProvider;

    public function __construct(?string $bucketProvider)
    {
        $this->bucketProvider = $bucketProvider;
    }

    /**
     * Lists all LsItem entities.
     *
     * @Route("/", methods={"GET"}, name="lsitem_index")
     * @Template()
     */
    public function indexAction(): array
    {
        $em = $this->getDoctrine()->getManager();

        $lsItems = $em->getRepository(LsItem::class)->findAll();

        return [
            'lsItems' => $lsItems,
        ];
    }

    /**
     * Creates a new LsItem entity.
     *
     * @Route("/new/{doc}/{parent}", methods={"GET", "POST"}, name="lsitem_new")
     * @Route("/new/{doc}/{parent}/{assocGroup}", methods={"GET", "POST"}, name="lsitem_new_ag")
     * @Template()
     * @Security("is_granted('add-standard-to', doc)")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, LsDoc $doc, ?LsItem $parent = null, ?LsDefAssociationGrouping $assocGroup = null)
    {
        $ajax = $request->isXmlHttpRequest();

        $lsItem = new LsItem();

        $lsItem->setLsDoc($doc);
        $lsItem->setLsDocUri($doc->getUri());

        $form = $this->createForm(LsItemType::class, $lsItem, ['ajax' => $ajax]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddItemCommand($lsItem, $doc, $parent, $assocGroup);
                $this->sendCommand($command);

                // retrieve isChildOf assoc id for the new item
                /** @var LsAssociation $assoc */
                $assoc = $this->getDoctrine()->getRepository(LsAssociation::class)->findOneBy(['originLsItem' => $lsItem]);

                if ($ajax) {
                    // if ajax call, return the item as json
                    return $this->generateItemJsonResponse($lsItem, $assoc);
                }

                return $this->redirectToRoute('lsitem_show', ['id' => $lsItem->getId()]);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new item: '.$e->getMessage()));
            }
        }

        $ret = [
            'lsItem' => $lsItem,
            'form' => $form->createView(),
        ];

        if ($ajax && $form->isSubmitted()) {
            return $this->render('framework/ls_item/new.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    /**
     * Finds and displays a LsItem entity.
     *
     * @Route("/{id}.{_format}", methods={"GET"}, defaults={"_format"="html"}, name="lsitem_show")
     * @Template()
     *
     * @param LsItem $lsItem
     * @param string $_format
     *
     * @return array
     */
    public function showAction(LsItem $lsItem, $_format = 'html')
    {
        if ('json' === $_format) {
            // Redirect?  Change Action for Template?
            return ['lsItem' => $lsItem];
        }

        $deleteForm = $this->createDeleteForm($lsItem);

        return [
            'lsItem' => $lsItem,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsItem entity.
     *
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="lsitem_edit")
     * @Template()
     * @Security("is_granted('edit', lsItem)")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, LsItem $lsItem, UserInterface $user)
    {
        $ajax = $request->isXmlHttpRequest();

        try {
            $command = new LockItemCommand($lsItem, $user);
            $this->sendCommand($command);
        } catch (AlreadyLockedException $e) {
            return $this->render(
                'framework/ls_item/locked.html.twig',
                []
            );
        }

        $deleteForm = $this->createDeleteForm($lsItem);
        $editForm = $this->createForm(LsItemType::class, $lsItem, ['ajax' => $ajax]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateItemCommand($lsItem);
                $this->sendCommand($command);

                if ($ajax) {
                    // if ajax call, return the item as json
                    return $this->generateItemJsonResponse($lsItem);
                }

                return $this->redirectToRoute('lsitem_edit', ['id' => $lsItem->getId()]);
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating item: '.$e->getMessage()));
            }
        }

        $ret = [
            'lsItem' => $lsItem,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];

        if ($ajax && $editForm->isSubmitted() && !$editForm->isValid()) {
            return $this->render('framework/ls_item/edit.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    /**
     * Deletes a LsItem entity.
     *
     * @Route("/{id}", methods={"DELETE"}, name="lsitem_delete")
     * @Security("is_granted('edit', lsItem)")
     */
    public function deleteAction(Request $request, LsItem $lsItem): RedirectResponse
    {
        $form = $this->createDeleteForm($lsItem);
        $form->handleRequest($request);

        $hasChildren = $lsItem->getChildren();

        if ($form->isSubmitted() && $form->isValid() && $hasChildren->isEmpty()) {
            $command = new DeleteItemCommand($lsItem);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsitem_index');
    }

    /**
     * Creates a form to delete a LsItem entity.
     */
    private function createDeleteForm(LsItem $lsItem): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsitem_delete', ['id' => $lsItem->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Export an LsItem entity.
     *
     * @Route("/{id}/export", methods={"GET"}, defaults={"_format"="json"}, name="lsitem_export")
     * @Template()
     */
    public function exportAction(LsItem $lsItem): array
    {
        return [
            'lsItem' => $lsItem,
        ];
    }

    /**
     * Remove a child LSItem.
     *
     * @Route("/{id}/removeChild/{child}", methods={"POST"}, name="lsitem_remove_child")
     * @Security("is_granted('edit', lsItem)")
     * @Template()
     */
    public function removeChildAction(LsItem $parent, LsItem $child): array
    {
        $command = new RemoveChildCommand($parent, $child);
        $this->sendCommand($command);

        return [];
    }

    /**
     * Copy an LsItem to a new LsDoc.
     *
     * @Route("/{id}/copy", methods={"GET", "POST"}, name="lsitem_copy_item")
     * @Security("is_granted('edit', lsItem)")
     * @Template()
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function copyAction(Request $request, LsItem $lsItem)
    {
        // Steps
        //  - Select LsDoc to copy to
        //  - Clone LsItem to selected LsDoc

        $ajax = $request->isXmlHttpRequest();

        $command = new CopyToLsDocCommand();
        $form = $this->createForm(LsDocListType::class, $command->convertToDTO($lsItem), ['ajax' => $ajax]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $copyCommand = new CopyItemToDocCommand($form->getData());
            $this->sendCommand($copyCommand);
            $newItem = $copyCommand->getNewItem();

            if ($ajax) {
                return new Response(
                    $this->generateUrl('doc_tree_item_view', ['id' => $newItem->getId()]),
                    Response::HTTP_CREATED,
                    [
                        'Location' => $this->generateUrl('doc_tree_item_view', ['id' => $newItem->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
            }

            return $this->redirectToRoute('lsitem_show', ['id' => $lsItem->getId()]);
        }

        $ret = [
            'form' => $form->createView(),
        ];

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render('framework/ls_item/copy.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    /**
     * Displays a form to change the parent of an existing LsItem entity.
     *
     * @Route("/{id}/parent", methods={"GET", "POST"}, name="lsitem_change_parent")
     * @Security("is_granted('edit', lsItem)")
     * @Template()
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function changeParentAction(Request $request, LsItem $lsItem)
    {
        $ajax = $request->isXmlHttpRequest();

        $lsDoc = $lsItem->getLsDoc();

        $command = new ChangeLsItemParentCommand();
        $form = $this->createForm(LsItemParentType::class, $command->convertToDTO($lsItem), ['ajax' => $ajax, 'lsDoc' => $lsDoc]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $changeCommand = new ChangeItemParentCommand($form->getData());
            $this->sendCommand($changeCommand);

            if ($ajax) {
                return new Response($this->generateUrl('doc_tree_item_view', ['id' => $lsItem->getId()]), Response::HTTP_ACCEPTED);
            }

            return $this->redirectToRoute('lsitem_edit', ['id' => $lsItem->getId()]);
        }

        $ret = [
            'lsItem' => $lsItem,
            'lsDoc' => $lsDoc,
            'form' => $form->createView(),
        ];

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render('framework/ls_item/change_parent.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    /**
     * Upload attachment to LsItem entity.
     *
     * @Route("/{id}/upload_attachment", methods={"POST"}, name="lsitem_upload_attachment")
     * @Template()
     * @Security("is_granted('add-standard-to', doc)")
     */
    public function uploadAttachmentAction(Request $request, LsDoc $doc, BucketService $bucket): Response
    {
        if (!empty($this->bucketProvider)) {
            $file = $request->files->get('file');

            if (null !== $file && $file->isValid()) {
                $fileUrl = $bucket->uploadFile($file, 'items');
                return new JsonResponse(['filename' => $fileUrl]);
            }
        }

        return new Response(null, Response::HTTP_BAD_REQUEST);
    }

    private function generateItemJsonResponse(LsItem $item, ?LsAssociation $assoc = null): Response
    {
        $ret = [
            'id' => $item->getId(),
            'identifier' => $item->getIdentifier(),
            'uri' => $item->getUri(),
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
                    //'groupId' => (null !== $assoc->getGroup()) ? $assoc->getGroup()->getId() : null,
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

        $response = new Response($this->renderView('framework/doc_tree/export_item.json.twig', ['lsItem' => $ret]));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
