<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddLicenceCommand;
use App\Command\Framework\DeleteLicenceCommand;
use App\Command\Framework\UpdateLicenceCommand;
use App\Entity\Framework\LsDefLicence;
use App\Form\Type\LsDefLicenceType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/cfdef/licence')]
class LsDefLicenceController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefLicence entities.
     *
     * @return array
     */
    #[Route(path: '/', name: 'lsdef_licence_index', methods: ['GET'])]
    #[Template]
    public function indexAction()
    {
        $em = $this->managerRegistry->getManager();

        $lsDefLicences = $em->getRepository(LsDefLicence::class)->findBy([], null, 100);

        return [
            'lsDefLicences' => $lsDefLicences,
        ];
    }

    /**
     * Lists all LsDefLicence entities.
     */
    #[Route(path: '/list.{_format}', name: 'lsdef_licence_index_json', defaults: ['_format' => 'json'], methods: ['GET'])]
    #[Template]
    public function jsonListAction(): array
    {
        $em = $this->managerRegistry->getManager();

        $objects = $em->getRepository(LsDefLicence::class)->getList();

        return [
            'objects' => $objects,
        ];
    }

    /**
     * Creates a new LsDefLicence entity.
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/new', name: 'lsdef_licence_new', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('create', 'lsdoc')")]
    public function newAction(Request $request)
    {
        $lsDefLicence = new LsDefLicence();
        $form = $this->createForm(LsDefLicenceType::class, $lsDefLicence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddLicenceCommand($lsDefLicence);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_licence_show', ['id' => $lsDefLicence->getId()]);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding licence: '.$e->getMessage()));
            }
        }

        return [
            'lsDefLicence' => $lsDefLicence,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefLicence entity.
     *
     * @return array
     */
    #[Route(path: '/{id}', name: 'lsdef_licence_show', methods: ['GET'])]
    #[Template]
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
     * @return array|RedirectResponse
     */
    #[Route(path: '/{id}/edit', name: 'lsdef_licence_edit', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('create', 'lsdoc')")]
    public function editAction(Request $request, LsDefLicence $lsDefLicence)
    {
        $deleteForm = $this->createDeleteForm($lsDefLicence);
        $editForm = $this->createForm(LsDefLicenceType::class, $lsDefLicence);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateLicenceCommand($lsDefLicence);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_licence_edit', ['id' => $lsDefLicence->getId()]);
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating licence: '.$e->getMessage()));
            }
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
     * @return RedirectResponse
     */
    #[Route(path: '/{id}', name: 'lsdef_licence_delete', methods: ['DELETE'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function deleteAction(Request $request, LsDefLicence $lsDefLicence)
    {
        $form = $this->createDeleteForm($lsDefLicence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteLicenceCommand($lsDefLicence);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_licence_index');
    }

    /**
     * Creates a form to delete a LsDefLicence entity.
     */
    private function createDeleteForm(LsDefLicence $lsDefLicence): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_licence_delete', ['id' => $lsDefLicence->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
