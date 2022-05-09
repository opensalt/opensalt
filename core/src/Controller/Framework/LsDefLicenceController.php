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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     */
    #[Route(path: '/', name: 'lsdef_licence_index', methods: ['GET'])]
    public function index(): Response
    {
        $em = $this->managerRegistry->getManager();

        $lsDefLicences = $em->getRepository(LsDefLicence::class)->findBy([], null, 100);

        return $this->render('framework/ls_def_licence/index.html.twig', [
            'lsDefLicences' => $lsDefLicences,
        ]);
    }

    /**
     * Lists all LsDefLicence entities.
     */
    #[Route(path: '/list.{_format}', name: 'lsdef_licence_index_json', defaults: ['_format' => 'json'], methods: ['GET'])]
    public function jsonList(): Response
    {
        $em = $this->managerRegistry->getManager();

        $objects = $em->getRepository(LsDefLicence::class)->getList();

        return $this->render('framework/ls_def_licence/json_list.json.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * Creates a new LsDefLicence entity.
     */
    #[Route(path: '/new', name: 'lsdef_licence_new', methods: ['GET', 'POST'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function new(Request $request): Response
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

        return $this->render('framework/ls_def_licence/new.html.twig', [
            'lsDefLicence' => $lsDefLicence,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a LsDefLicence entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_licence_show', methods: ['GET'])]
    public function show(LsDefLicence $lsDefLicence): Response
    {
        $deleteForm = $this->createDeleteForm($lsDefLicence);

        return $this->render('framework/ls_def_licence/show.html.twig', [
            'lsDefLicence' => $lsDefLicence,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing LsDefLicence entity.
     */
    #[Route(path: '/{id}/edit', name: 'lsdef_licence_edit', methods: ['GET', 'POST'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function edit(Request $request, LsDefLicence $lsDefLicence): Response
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

        return $this->render('framework/ls_def_licence/edit.html.twig', [
            'lsDefLicence' => $lsDefLicence,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a LsDefLicence entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_licence_delete', methods: ['DELETE'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function delete(Request $request, LsDefLicence $lsDefLicence): RedirectResponse
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
