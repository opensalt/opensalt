<?php

namespace CftfBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDefConcept;

/**
 * LsDefConcept controller.
 *
 * @Route("/lsdef/concept")
 */
class LsDefConceptController extends Controller
{
    /**
     * Lists all LsDefConcept entities.
     *
     * @Route("/", name="lsdef_concept_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefConcepts = $em->getRepository('CftfBundle:LsDefConcept')->findAll();

        return [
            'lsDefConcepts' => $lsDefConcepts,
        ];
    }

    /**
     * Creates a new LsDefConcept entity.
     *
     * @Route("/new", name="lsdef_concept_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request)
    {
        $lsDefConcept = new LsDefConcept();
        $form = $this->createForm('CftfBundle\Form\LsDefConceptType', $lsDefConcept);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefConcept);
            $em->flush();

            return $this->redirectToRoute('lsdef_concept_show', array('id' => $lsDefConcept->getId()));
        }

        return [
            'lsDefConcept' => $lsDefConcept,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefConcept entity.
     *
     * @Route("/{id}", name="lsdef_concept_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(LsDefConcept $lsDefConcept)
    {
        $deleteForm = $this->createDeleteForm($lsDefConcept);

        return [
            'lsDefConcept' => $lsDefConcept,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefConcept entity.
     *
     * @Route("/{id}/edit", name="lsdef_concept_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, LsDefConcept $lsDefConcept)
    {
        $deleteForm = $this->createDeleteForm($lsDefConcept);
        $editForm = $this->createForm('CftfBundle\Form\LsDefConceptType', $lsDefConcept);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefConcept);
            $em->flush();

            return $this->redirectToRoute('lsdef_concept_edit', array('id' => $lsDefConcept->getId()));
        }

        return [
            'lsDefConcept' => $lsDefConcept,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefConcept entity.
     *
     * @Route("/{id}", name="lsdef_concept_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, LsDefConcept $lsDefConcept)
    {
        $form = $this->createDeleteForm($lsDefConcept);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($lsDefConcept);
            $em->flush();
        }

        return $this->redirectToRoute('lsdef_concept_index');
    }

    /**
     * Creates a form to delete a LsDefConcept entity.
     *
     * @param LsDefConcept $lsDefConcept The LsDefConcept entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsDefConcept $lsDefConcept)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_concept_delete', array('id' => $lsDefConcept->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
