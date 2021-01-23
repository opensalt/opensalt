<?php

namespace App\Controller\User;

use App\Command\CommandDispatcherTrait;
use App\Command\User\AddOrganizationCommand;
use App\Command\User\DeleteOrganizationCommand;
use App\Command\User\UpdateOrganizationCommand;
use App\Entity\User\Organization;
use App\Form\Type\OrganizationType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Organization controller.
 *
 * @Route("/admin/organization")
 * @Security("is_granted('manage', 'organizations')")
 */
class OrganizationController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * Lists all organization entities.
     *
     * @Route("/", methods={"GET"}, name="admin_organization_index")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $organizations = $em->getRepository(Organization::class)->findAll();

        return [
            'organizations' => $organizations,
        ];
    }

    /**
     * Creates a new organization entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="admin_organization_new")
     * @Template()
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $organization = new Organization();
        $form = $this->createForm(OrganizationType::class, $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddOrganizationCommand($organization);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_organization_index');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return [
            'organization' => $organization,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a organization entity.
     *
     * @Route("/{id}", methods={"GET"}, name="admin_organization_show")
     * @Template()
     *
     * @return array
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
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="admin_organization_edit")
     * @Template()
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, Organization $organization)
    {
        $deleteForm = $this->createDeleteForm($organization);
        $editForm = $this->createForm(OrganizationType::class, $organization);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateOrganizationCommand($organization);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_organization_index');
            } catch (\Exception $e) {
                $editForm->addError(new FormError($e->getMessage()));
            }
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
     * @Route("/{id}", methods={"DELETE"}, name="admin_organization_delete")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, Organization $organization): Response
    {
        $form = $this->createDeleteForm($organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteOrganizationCommand($organization);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('admin_organization_index');
    }

    /**
     * Creates a form to delete a organization entity.
     *
     * @param Organization $organization The organization entity
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(Organization $organization): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_organization_delete', array('id' => $organization->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
