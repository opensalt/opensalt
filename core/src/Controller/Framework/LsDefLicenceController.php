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

/**
 * LsDefLicence controller.
 */
#[Route(path: '/cfdef/licence')]
class LsDefLicenceController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefLicence entities.
     *
     * @Template()
     *
     * @return array
     */
    #[Route(path: '/', methods: ['GET'], name: 'lsdef_licence_index')]
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
     *
     * @Template()
     */
    #[Route(path: '/list.{_format}', methods: ['GET'], defaults: ['_format' => 'json'], name: 'lsdef_licence_index_json')]
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
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/new', methods: ['GET', 'POST'], name: 'lsdef_licence_new')]
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
     * @Template()
     *
     * @return array
     */
    #[Route(path: '/{id}', methods: ['GET'], name: 'lsdef_licence_show')]
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
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{id}/edit', methods: ['GET', 'POST'], name: 'lsdef_licence_edit')]
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
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return RedirectResponse
     */
    #[Route(path: '/{id}', methods: ['DELETE'], name: 'lsdef_licence_delete')]
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
     *
     * @param LsDefLicence $lsDefLicence The LsDefLicence entity
     *
     * @return FormInterface The form
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
