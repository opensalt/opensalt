<?php

namespace Salt\UserBundle\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\User\AddOrganizationCommand;
use App\Command\User\DeleteOrganizationCommand;
use App\Command\User\UpdateOrganizationCommand;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Form\Type\OrganizationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Organization controller.
 *
 * @Route("admin/organization")
 * @Security("has_role('ROLE_SUPER_USER')")
 */
class OrganizationController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * Lists all organization entities.
     *
     * @Route("/", name="admin_organization_index")
     * @Method("GET")
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
     * @Route("/new", name="admin_organization_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
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
     * @Route("/{id}", name="admin_organization_show")
     * @Method("GET")
     * @Template()
     *
     * @param Organization $organization
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
     * @Route("/{id}/edit", name="admin_organization_edit")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param Organization $organization
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
     * @Route("/{id}", name="admin_organization_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param Organization $organization
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
