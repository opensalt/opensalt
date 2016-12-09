<?php

namespace Salt\UserBundle\Controller;

use Salt\UserBundle\Entity\Organization;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Organization controller.
 *
 * @Route("admin/organization")
 * @Security("has_role('ROLE_SUPER_USER')")
 */
class OrganizationController extends Controller
{
    /**
     * Lists all organization entities.
     *
     * @Route("/", name="admin_organization_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $organizations = $em->getRepository('SaltUserBundle:Organization')->findAll();

        return [
            'organizations' => $organizations,
        ];
    }

    /**
     * Creates a new organization entity.
     *
     * @Route("/new", name="admin_organization_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request)
    {
        $organization = new Organization();
        $form = $this->createForm('Salt\UserBundle\Form\OrganizationType', $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organization);
            $em->flush($organization);

            return $this->redirectToRoute('admin_organization_show', array('id' => $organization->getId()));
        }

        return [
            'organization' => $organization,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a organization entity.
     *
     * @Route("/{id}", name="admin_organization_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Organization $organization)
    {
        $deleteForm = $this->createDeleteForm($organization);

        return [
            'organization' => $organization,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing organization entity.
     *
     * @Route("/{id}/edit", name="admin_organization_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, Organization $organization)
    {
        $deleteForm = $this->createDeleteForm($organization);
        $editForm = $this->createForm('Salt\UserBundle\Form\OrganizationType', $organization);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_organization_edit', array('id' => $organization->getId()));
        }

        return [
            'organization' => $organization,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a organization entity.
     *
     * @Route("/{id}", name="admin_organization_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Organization $organization)
    {
        $form = $this->createDeleteForm($organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($organization);
            $em->flush($organization);
        }

        return $this->redirectToRoute('admin_organization_index');
    }

    /**
     * Creates a form to delete a organization entity.
     *
     * @param Organization $organization The organization entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Organization $organization)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_organization_delete', array('id' => $organization->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
