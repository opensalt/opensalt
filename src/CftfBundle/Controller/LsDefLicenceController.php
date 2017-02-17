<?php

namespace CftfBundle\Controller;

use CftfBundle\Form\Type\LsDefLicenceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDefLicence;

/**
 * LsDefLicence controller.
 *
 * @Route("/cfdef/licence")
 */
class LsDefLicenceController extends Controller
{
    /**
     * Lists all LsDefLicence entities.
     *
     * @Route("/", name="lsdef_licence_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefLicences = $em->getRepository('CftfBundle:LsDefLicence')->findAll();

        return [
            'lsDefLicences' => $lsDefLicences,
        ];
    }

    /**
     * Creates a new LsDefLicence entity.
     *
     * @Route("/new", name="lsdef_licence_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $lsDefLicence = new LsDefLicence();
        $form = $this->createForm(LsDefLicenceType::class, $lsDefLicence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefLicence);
            $em->flush();

            return $this->redirectToRoute('lsdef_licence_show', array('id' => $lsDefLicence->getId()));
        }

        return [
            'lsDefLicence' => $lsDefLicence,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefLicence entity.
     *
     * @Route("/{id}", name="lsdef_licence_show")
     * @Method("GET")
     * @Template()
     *
     * @param LsDefLicence $lsDefLicence
     *
     * @return array
     */
    public function showAction(LsDefLicence $lsDefLicence)
    {
        $deleteForm = $this->createDeleteForm($lsDefLicence);

        return [
            'lsDefLicence' => $lsDefLicence,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefLicence entity.
     *
     * @Route("/{id}/edit", name="lsdef_licence_edit")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsDefLicence $lsDefLicence
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsDefLicence $lsDefLicence)
    {
        $deleteForm = $this->createDeleteForm($lsDefLicence);
        $editForm = $this->createForm(LsDefLicenceType::class, $lsDefLicence);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefLicence);
            $em->flush();

            return $this->redirectToRoute('lsdef_licence_edit', array('id' => $lsDefLicence->getId()));
        }

        return [
            'lsDefLicence' => $lsDefLicence,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefLicence entity.
     *
     * @Route("/{id}", name="lsdef_licence_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param LsDefLicence $lsDefLicence
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsDefLicence $lsDefLicence)
    {
        $form = $this->createDeleteForm($lsDefLicence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($lsDefLicence);
            $em->flush();
        }

        return $this->redirectToRoute('lsdef_licence_index');
    }

    /**
     * Creates a form to delete a LsDefLicence entity.
     *
     * @param LsDefLicence $lsDefLicence The LsDefLicence entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsDefLicence $lsDefLicence)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_licence_delete', array('id' => $lsDefLicence->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
