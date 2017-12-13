<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcher;
use App\Command\Framework\AddAssociationCommand;
use App\Command\Framework\AddExemplarToItemCommand;
use App\Command\Framework\AddTreeAssociationCommand;
use App\Command\Framework\DeleteAssociationCommand;
use App\Command\Framework\UpdateAssociationCommand;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefAssociationGrouping;
use CftfBundle\Form\Type\LsAssociationType;
use Symfony\Component\HttpFoundation\Response;

/**
 * LsAssociation controller.
 *
 * @Route("/cfassociation")
 */
class LsAssociationController extends Controller
{
    use CommandDispatcher;

    /**
     * Lists all LsAssociation entities.
     *
     * @Route("/", name="lsassociation_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsAssociations = $em->getRepository(LsAssociation::class)->findAll();

        return [
            'lsAssociations' => $lsAssociations,
        ];
    }

    /**
     * Creates a new LsAssociation entity.
     *
     * @Route("/new/{sourceLsItem}", name="lsassociation_new")
     * @Route("/new/{sourceLsItem}/{assocGroup}", name="lsassociation_new_ag")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsItem|null $sourceLsItem
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @return array|RedirectResponse|Response
     */
    public function newAction(Request $request, ?LsItem $sourceLsItem = null, ?LsDefAssociationGrouping $assocGroup = null)
    {
        // @TODO: Add LsDoc of the new association for when adding via AJAX
        $ajax = $request->isXmlHttpRequest();

        $lsAssociation = new LsAssociation();
        if ($sourceLsItem) {
            $lsAssociation->setOriginLsItem($sourceLsItem);

            // Default to adding to source item's LsDoc
            $lsAssociation->setLsDoc($sourceLsItem->getLsDoc());
        }

        // set assocGroup if provided
        if ($assocGroup !== null) {
            $lsAssociation->setGroup($assocGroup);
        }

        $form = $this->createForm(LsAssociationType::class, $lsAssociation, ['ajax'=>$ajax]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddAssociationCommand($lsAssociation);
                $this->sendCommand($command);

                if ($ajax) {
                    return new Response($this->generateUrl('doc_tree_item_view', ['id' => $sourceLsItem->getId()]), Response::HTTP_CREATED);
                }

                return $this->redirectToRoute('lsassociation_show', array('id' => $lsAssociation->getId()));
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new association: '.$e->getMessage()));
            }
        }

        $lsDoc = $form->get('lsDoc')->getData();

        $ret = [
            'lsAssociation' => $lsAssociation,
            'form' => $form->createView(),
            'lsDoc' => $lsDoc,
        ];

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            //return $this->render('CftfBundle:LsAssociation:new.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
            return $this->render('CftfBundle:LsAssociation:new.html.twig', $ret, new Response('', Response::HTTP_OK));
        }

        return $ret;
    }

    /**
     * Creates a new LsAssociation entity -- tree-view version, called via ajax
     *
     * @Route("/treenew/{lsDoc}", name="lsassociation_tree_new")
     * @Method("POST")
     *
     * @param Request $request
     * @param LsDoc $lsDoc  : the document we're adding the association to
     *
     * @return Response
     */
    public function treeNewAction(Request $request, LsDoc $lsDoc): Response
    {
        // type, origin['externalDoc', 'id', 'identifier'], dest['externalDoc', 'id', 'identifier'], assocGroup
        foreach (['type', 'origin', 'dest'] as $value) {
            if (!$request->request->has($value)) {
                return new JsonResponse(['error' => ['message' => "Missing value: {$value}"]], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $command = new AddTreeAssociationCommand(
                $lsDoc,
                $request->request->get('origin'),
                $request->request->get('type'),
                $request->request->get('dest'),
                $request->request->get('assocGroup')
            );
            $this->sendCommand($command);
            $lsAssociation = $command->getAssociation();

            // return id of created association
            return new Response((null !== $lsAssociation) ? $lsAssociation->getId() : '', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => ['message' => $e->getMessage()]], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Creates a new LsAssociation entity for an exemplar
     *
     * @Route("/treenewexemplar/{originLsItem}", name="lsassociation_tree_new_exemplar")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param LsItem $originLsItem
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     */
    public function treeNewExemplarAction(Request $request, LsItem $originLsItem): Response
    {
        if (!$request->request->has('exemplarUrl')) {
            return new JsonResponse(['error' => ['message' => 'Missing value: exemplarUrl']], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new AddExemplarToItemCommand($originLsItem, $request->request->get('exemplarUrl'));
            $this->sendCommand($command);
            $lsAssociation = $command->getAssociation();

            $rv = [
                'id' => isset($lsAssociation) ? $lsAssociation->getId() : null,
                'identifier' => isset($lsAssociation) ? $lsAssociation->getIdentifier() : null
            ];

            $response = new JsonResponse($rv);
            $response->headers->set('Cache-Control', 'no-cache');

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse(['error' => ['message' => $e->getMessage()]], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Finds and displays a LsAssociation entity.
     *
     * @Route("/{id}", name="lsassociation_show")
     * @Method("GET")
     * @Template()
     *
     * @param LsAssociation $lsAssociation
     *
     * @return array
     */
    public function showAction(LsAssociation $lsAssociation)
    {
        $deleteForm = $this->createDeleteForm($lsAssociation);

        return [
            'lsAssociation' => $lsAssociation,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsAssociation entity.
     *
     * @Route("/{id}/edit", name="lsassociation_edit")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsAssociation $lsAssociation
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, LsAssociation $lsAssociation)
    {
        $deleteForm = $this->createDeleteForm($lsAssociation);
        $editForm = $this->createForm(LsAssociationType::class, $lsAssociation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateAssociationCommand($lsAssociation);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsassociation_edit', array('id' => $lsAssociation->getId()));
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating new association: '.$e->getMessage()));
            }
        }

        return [
            'lsAssociation' => $lsAssociation,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsAssociation entity.
     *
     * @Route("/{id}", name="lsassociation_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param LsAssociation $lsAssociation
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, LsAssociation $lsAssociation)
    {
        $form = $this->createDeleteForm($lsAssociation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteAssociationCommand($lsAssociation);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsassociation_index');
    }

    /**
     * Remove a child LSItem
     *
     * @Route("/{id}/remove", name="lsassociation_remove")
     * @Method("POST")
     * @Template()
     *
     * @param \CftfBundle\Entity\LsAssociation $lsAssociation
     *
     * @return array
     */
    public function removeChildAction(LsAssociation $lsAssociation)
    {
        $command = new DeleteAssociationCommand($lsAssociation);
        $this->sendCommand($command);

        return [];
    }

    /**
     * Export an LsAssociation entity.
     *
     * @Route("/{id}/export", defaults={"_format"="json"}, name="lsassociation_export")
     * @Method("GET")
     * @Template()
     *
     * @param LsAssociation $lsAssociation
     *
     * @return array
     */
    public function exportAction(LsAssociation $lsAssociation)
    {
        return [
            'lsAssociation' => $lsAssociation,
        ];
    }

    /**
     * Creates a form to delete a LsAssociation entity.
     *
     * @param LsAssociation $lsAssociation The LsAssociation entity
     *
     * @return FormInterface The form
     */
    private function createDeleteForm(LsAssociation $lsAssociation): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsassociation_delete', array('id' => $lsAssociation->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
