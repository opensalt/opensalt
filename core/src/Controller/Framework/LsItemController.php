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
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Exception\AlreadyLockedException;
use App\Form\Command\ChangeLsItemParentCommand;
use App\Form\Command\CopyToLsDocCommand;
use App\Form\Type\LsDocListType;
use App\Form\Type\LsItemParentType;
use App\Form\Type\LsItemType;
use App\Service\BucketService;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LsItem controller.
 */
#[Route(path: '/cfitem')]
class LsItemController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ?string $bucketProvider,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsItem entities.
     */
    #[Route(path: '/', name: 'lsitem_index', methods: ['GET'])]
    #[Template]
    public function index(): array
    {
        return [];
    }

    /**
     * Creates a new LsItem entity.
     *
     * @return array|RedirectResponse|Response
     */
    #[Route(path: '/new/{doc}/{parent}', name: 'lsitem_new', methods: ['GET', 'POST'])]
    #[Route(path: '/new/{doc}/{parent}/{assocGroup}', name: 'lsitem_new_ag', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('add-standard-to', doc)")]
    public function new(Request $request, LsDoc $doc, ?LsItem $parent = null, ?LsDefAssociationGrouping $assocGroup = null)
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
                $assoc = $this->managerRegistry->getRepository(LsAssociation::class)->findOneBy(['originLsItem' => $lsItem]);

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
     * @return array
     */
    #[Route(path: '/{id}.{_format}', name: 'lsitem_show', defaults: ['_format' => 'html'], methods: ['GET'])]
    #[Template]
    public function show(LsItem $lsItem, string $_format = 'html')
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
     * @return array|RedirectResponse|Response
     */
    #[Route(path: '/{id}/edit', name: 'lsitem_edit', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('edit', lsItem)")]
    public function edit(Request $request, LsItem $lsItem, UserInterface $user)
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
     */
    #[Route(path: '/{id}', name: 'lsitem_delete', methods: ['DELETE'])]
    #[Security("is_granted('edit', lsItem)")]
    public function delete(Request $request, LsItem $lsItem): RedirectResponse
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
     */
    #[Route(path: '/{id}/export', name: 'lsitem_export', defaults: ['_format' => 'json'], methods: ['GET'])]
    #[Template]
    public function export(LsItem $lsItem): array
    {
        return [
            'lsItem' => $lsItem,
        ];
    }

    /**
     * Remove a child LSItem.
     */
    #[Route(path: '/{id}/removeChild/{child}', name: 'lsitem_remove_child', methods: ['POST'])]
    #[Security("is_granted('edit', lsItem)")]
    #[Template]
    public function removeChild(LsItem $parent, LsItem $child): array
    {
        $command = new RemoveChildCommand($parent, $child);
        $this->sendCommand($command);

        return [];
    }

    /**
     * Copy an LsItem to a new LsDoc.
     *
     * @return array|RedirectResponse|Response
     */
    #[Route(path: '/{id}/copy', name: 'lsitem_copy_item', methods: ['GET', 'POST'])]
    #[Security("is_granted('edit', lsItem)")]
    #[Template]
    public function copy(Request $request, LsItem $lsItem)
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
     * @return array|RedirectResponse|Response
     */
    #[Route(path: '/{id}/parent', name: 'lsitem_change_parent', methods: ['GET', 'POST'])]
    #[Security("is_granted('edit', lsItem)")]
    #[Template]
    public function changeParent(Request $request, LsItem $lsItem)
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
     */
    #[Route(path: '/{id}/upload_attachment', name: 'lsitem_upload_attachment', methods: ['POST'])]
    #[Template]
    #[Security("is_granted('add-standard-to', doc)")]
    public function uploadAttachment(Request $request, LsDoc $doc, BucketService $bucket): Response
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
