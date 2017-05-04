<?php

namespace CftfBundle\Controller;

use Ramsey\Uuid\Uuid;
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
     * @Route("/new/{sourceLsItem}/{assocGroup}", name="lsassociation_new_ag")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsItem|null $sourceLsItem
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, ?LsItem $sourceLsItem = null, ?LsDefAssociationGrouping $assocGroup = null)
    {
        // @TODO: Add LsDoc of the new association for when adding via AJAX
        $ajax = $request->isXmlHttpRequest();

        $lsAssociation = new LsAssociation();
        if ($sourceLsItem) {
            $lsAssociation->setOriginLsItem($sourceLsItem);
        }

        // PW: set assocGroup if provided and non-null
        if ($assocGroup !== null) {
            $lsAssociation->setGroup($assocGroup);
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
     * Creates a new LsAssociation entity -- tree-view version, called via ajax (PW).
     *
     * @Route("/treenew/{lsDoc}", name="lsassociation_tree_new")
     * @Method("POST")
     *
     * @param Request $request
     * @param LsDoc $lsDoc  : the document we're adding the association to
     *
     * @return Response
     */
    public function treeNewAction(Request $request, LsDoc $lsDoc)
    {
        $em = $this->getDoctrine()->getManager();
        $lsAssociation = new LsAssociation();

        $lsAssociation->setType($request->request->get('type'));

        // Add to the provided LsDoc
        $lsAssociation->setLsDoc($lsDoc);

        // deal with origin and dest items, which can be specified by id or by identifier
        // if externalDoc is specified for either one, mark this document as "autoLoad": "true" in the lsDoc's externalDocuments
        $repo = $em->getRepository(LsItem::class);

        $origin = $request->request->get('origin');
        if (!empty($origin['id'])) {
            $origin = $repo->findOneBy(['id'=>$origin['id']]);
        } else {
            if (!empty($origin['externalDoc'])) {
                $lsDoc->setExternalDocAutoLoad($origin['externalDoc'], 'true');
                $em->persist($lsDoc);
            }
            $origin = $origin['identifier'];
        }

        $dest = $request->request->get('dest');
        if (!empty($dest['id'])) {
            $dest = $repo->findOneBy(['id'=>$dest['id']]);
        } else {
            if (!empty($dest['externalDoc'])) {
                $lsDoc->setExternalDocAutoLoad($dest['externalDoc'], 'true');
                $em->persist($lsDoc);
            }
            $dest = $dest['identifier'];
        }

        // setOrigin and setDestination will take care of setting things appropriately depending on whether an identifier or item are supplied
        $lsAssociation->setOrigin($origin);
        $lsAssociation->setDestination($dest);

        // set assocGroup if provided
        $assocGroup = $request->request->get('assocGroup');
        if (!empty($assocGroup)) {
            $repo = $em->getRepository(LsDefAssociationGrouping::class);
            $assocGroup = $repo->findOneBy(['id'=>$assocGroup]);
            $lsAssociation->setGroup($assocGroup);
        }

        $em->persist($lsAssociation);
        $em->flush();

        // return id of created association
        return new Response($lsAssociation->getId(), Response::HTTP_CREATED);
    }

    /**
     * Creates a new LsAssociation entity for an exemplar
     *
     * @Route("/treenewexemplar/{originLsItem}", name="lsassociation_tree_new_exemplar")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsItem $originLsItem
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function treeNewExemplarAction(Request $request, LsItem $originLsItem)
    {
        $lsAssociation = new LsAssociation();
        $lsAssociation->setLsDoc($originLsItem->getLsDoc());
        $lsAssociation->setOriginLsItem($originLsItem);
        $lsAssociation->setType(LsAssociation::EXEMPLAR);
        $lsAssociation->setDestinationNodeUri($request->request->get('exemplarUrl'));
        $lsAssociation->setDestinationNodeIdentifier(Uuid::uuid5(Uuid::NAMESPACE_URL, $lsAssociation->getDestinationNodeUri()));
        // TODO: setDestinationTitle is not currently a table field.
        //$lsAssociation->setDestinationTitle($request->request->get("exemplarDescription"));

        $em = $this->getDoctrine()->getManager();
        $em->persist($lsAssociation);
        $em->flush();

        $rv = [
            'id' => $lsAssociation->getId(),
            'identifier' => $lsAssociation->getIdentifier()
        ];

        $response = new Response(json_encode($rv));
        $response->headers->set('Content-Type', 'text/json');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
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
