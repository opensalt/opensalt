<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsAssociation;
use CftfBundle\Form\Type\LsAssociationType;
use CftfBundle\Form\Type\LsAssociationTreeType;
use Symfony\Component\HttpFoundation\Response;

/**
 * LsAssociation controller.
 *
 * @Route("/cfassociation")
 */
class LsAssociationController extends Controller
{
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

        $lsAssociations = $em->getRepository('CftfBundle:LsAssociation')->findAll();

        return [
            'lsAssociations' => $lsAssociations,
        ];
    }

    /**
     * Creates a new LsAssociation entity.
     *
     * @Route("/new/{sourceLsItem}", name="lsassociation_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsItem|null $sourceLsItem
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, LsItem $sourceLsItem = null)
    {
        // @TODO: Add LsDoc of the new association for when adding via AJAX
        $ajax = $request->isXmlHttpRequest();

        $lsAssociation = new LsAssociation();
        if ($sourceLsItem) {
            $lsAssociation->setOriginLsItem($sourceLsItem);
        }

        $form = $this->createForm(LsAssociationType::class, $lsAssociation, ['ajax'=>$ajax]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($sourceLsItem) {
                // Default to adding to source item's LsDoc
                $lsAssociation->setLsDoc($sourceLsItem->getLsDoc());
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($lsAssociation);
            $em->flush();

            if ($ajax) {
                return new Response($this->generateUrl('doc_tree_item_view', ['id' => $sourceLsItem->getId()]), Response::HTTP_CREATED);
            }

            return $this->redirectToRoute('lsassociation_show', array('id' => $lsAssociation->getId()));
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
     * Creates a new LsAssociation entity -- tree-view version (PW).
     *
     * @Route("/treenew/{originLsItem}/{destinationLsItem}", name="lsassociation_tree_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsItem|null $originLsItem
     * @param LsItem|null $destinationLsItem
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function treeNewAction(Request $request, LsItem $originLsItem = null, LsItem $destinationLsItem = null)
    {
        $ajax = $request->isXmlHttpRequest();

        $lsAssociation = new LsAssociation();
        $lsAssociation->setOriginLsItem($originLsItem);
        $lsAssociation->setDestinationLsItem($destinationLsItem);
        // Add to the origin item's LsDoc
        $lsAssociation->setLsDoc($originLsItem->getLsDoc());

        $form = $this->createForm(LsAssociationTreeType::class, $lsAssociation, ['ajax'=>$ajax]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsAssociation);
            $em->flush();

            if ($ajax) {
                return new Response($this->generateUrl('doc_tree_item_view', ['id' => $destinationLsItem->getId()]), Response::HTTP_CREATED);
            }

            return $this->redirectToRoute('lsassociation_show', array('id' => $lsAssociation->getId()));
        }

        $ret = [
            'lsAssociation' => $lsAssociation,
            'form' => $form->createView(),
        ];

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render('CftfBundle:LsAssociation:new.html.twig', $ret, new Response('', Response::HTTP_OK));
        }

        return $ret;
    }
    
    /**
     * Creates a new LsAssociation entity for an exemplar
     *
     * @Route("/treenewexemplar/{originLsItem}", name="lsassociation_tree_new_exemplar")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsItem|null $originLsItem
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function treeNewExemplarAction(Request $request, LsItem $originLsItem = null)
    {
        $ajax = $request->isXmlHttpRequest();

        $lsAssociation = new LsAssociation();
        $lsAssociation->setOriginLsItem($originLsItem);
        $lsAssociation->setUri($request->request->get("exemplarUrl"));
        // TODO: setDescription is not currently a table field.
        //$lsAssociation->setDescription($request->request->get("exemplarDescription"));

        // Add to the origin item's LsDoc
        $lsAssociation->setLsDoc($originLsItem->getLsDoc());

		$em = $this->getDoctrine()->getManager();
		// TODO: the following line currently fails with the message "Integrity constraint violation: 1048 Column 'destination_node_identifier' cannot be null"
		//$em->persist($lsAssociation);
		$em->flush();
        
		return new Response($this->generateUrl('doc_tree_item_view', ['id' => $originLsItem->getId()]), Response::HTTP_CREATED);
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
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsAssociation $lsAssociation)
    {
        $deleteForm = $this->createDeleteForm($lsAssociation);
        $editForm = $this->createForm(LsAssociationType::class, $lsAssociation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsAssociation);
            $em->flush();

            return $this->redirectToRoute('lsassociation_edit', array('id' => $lsAssociation->getId()));
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsAssociation $lsAssociation)
    {
        $form = $this->createDeleteForm($lsAssociation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($lsAssociation);
            $em->flush();
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
        $em = $this->getDoctrine()->getManager();
        $em->remove($lsAssociation);
        $em->flush();

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
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsAssociation $lsAssociation)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsassociation_delete', array('id' => $lsAssociation->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
