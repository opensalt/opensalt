<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddConceptCommand;
use App\Command\Framework\DeleteConceptCommand;
use App\Command\Framework\UpdateConceptCommand;
use App\Entity\Framework\LsDefConcept;
use App\Form\Type\LsDefConceptType;
use App\Security\Permission;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/cfdef/concept')]
class LsDefConceptController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefConcept entities.
     */
    #[Route(path: '/', name: 'lsdef_concept_index', methods: ['GET'])]
    public function index(): Response
    {
        $em = $this->managerRegistry->getManager();

        $lsDefConcepts = $em->getRepository(LsDefConcept::class)->findBy([], null, 100);

        return $this->render('framework/ls_def_concept/index.html.twig', [
            'lsDefConcepts' => $lsDefConcepts,
        ]);
    }

    /**
     * Creates a new LsDefConcept entity.
     */
    #[Route(path: '/new', name: 'lsdef_concept_new', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function new(Request $request): Response
    {
        $lsDefConcept = new LsDefConcept();
        $form = $this->createForm(LsDefConceptType::class, $lsDefConcept);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddConceptCommand($lsDefConcept);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_concept_show', ['id' => $lsDefConcept->getId()]);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding concept: '.$e->getMessage()));
            }
        }

        return $this->render('framework/ls_def_concept/new.html.twig', [
            'lsDefConcept' => $lsDefConcept,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a LsDefConcept entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_concept_show', methods: ['GET'])]
    public function show(LsDefConcept $lsDefConcept): Response
    {
        $deleteForm = $this->createDeleteForm($lsDefConcept);

        return $this->render('framework/ls_def_concept/show.html.twig', [
            'lsDefConcept' => $lsDefConcept,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing LsDefConcept entity.
     */
    #[Route(path: '/{id}/edit', name: 'lsdef_concept_edit', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function edit(Request $request, LsDefConcept $lsDefConcept): Response
    {
        $deleteForm = $this->createDeleteForm($lsDefConcept);
        $editForm = $this->createForm(LsDefConceptType::class, $lsDefConcept);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateConceptCommand($lsDefConcept);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_concept_edit', ['id' => $lsDefConcept->getId()]);
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating concept: '.$e->getMessage()));
            }
        }

        return $this->render('framework/ls_def_concept/edit.html.twig', [
            'lsDefConcept' => $lsDefConcept,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a LsDefConcept entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_concept_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function delete(Request $request, LsDefConcept $lsDefConcept): RedirectResponse
    {
        $form = $this->createDeleteForm($lsDefConcept);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteConceptCommand($lsDefConcept);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_concept_index');
    }

    /**
     * Creates a form to delete a LsDefConcept entity.
     */
    private function createDeleteForm(LsDefConcept $lsDefConcept): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_concept_delete', ['id' => $lsDefConcept->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
