<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Command\CommandDispatcherTrait;
use App\Command\User\AddAccessGroupCommand;
use App\Command\User\DeleteAccessGroupCommand;
use App\Command\User\UpdateAccessGroupCommand;
use App\Entity\User\AccessGroup;
use App\Form\Type\AccessGroupType;
use App\Security\Permission;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/access_group')]
#[IsGranted(Permission::MANAGE_ACCESS_GROUPS)]
class AccessGroupController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all organization entities.
     */
    #[Route(path: '/', methods: ['GET'], name: 'admin_access_group_index')]
    public function index(): Response
    {
        $em = $this->managerRegistry->getManager();

        $organizations = $em->getRepository(AccessGroup::class)->findAll();

        return $this->render('user/access_group/index.html.twig', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Creates a new organization entity.
     */
    #[Route(path: '/new', name: 'admin_access_group_new', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $organization = new AccessGroup();
        $form = $this->createForm(AccessGroupType::class, $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddAccessGroupCommand($organization);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_access_group_index');
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('user/access_group/new.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays an organization entity.
     */
    #[Route(path: '/{id}', name: 'admin_access_group_show', methods: ['GET'])]
    public function show(AccessGroup $organization): Response
    {
        $deleteForm = $this->createDeleteForm($organization);

        return $this->render('user/access_group/show.html.twig', [
            'organization' => $organization,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing organization entity.
     */
    #[Route(path: '/{id}/edit', name: 'admin_access_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AccessGroup $organization): Response
    {
        $deleteForm = $this->createDeleteForm($organization);
        $editForm = $this->createForm(AccessGroupType::class, $organization);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateAccessGroupCommand($organization);
                $this->sendCommand($command);

                return $this->redirectToRoute('admin_access_group_index');
            } catch (\Exception $e) {
                $editForm->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('user/access_group/edit.html.twig', [
            'organization' => $organization,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes an organization entity.
     */
    #[Route(path: '/{id}', name: 'admin_access_group_delete', methods: ['DELETE'])]
    public function delete(Request $request, AccessGroup $organization): RedirectResponse
    {
        $form = $this->createDeleteForm($organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteAccessGroupCommand($organization);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('admin_access_group_index');
    }

    /**
     * Creates a form to delete an organization entity.
     */
    private function createDeleteForm(AccessGroup $organization): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_access_group_delete', ['id' => $organization->getId()]))
            ->setMethod(Request::METHOD_DELETE)
            ->getForm()
        ;
    }
}
