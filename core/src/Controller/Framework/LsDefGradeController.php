<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddGradeCommand;
use App\Command\Framework\DeleteGradeCommand;
use App\Command\Framework\UpdateGradeCommand;
use App\Entity\Framework\LsDefGrade;
use App\Form\Type\LsDefGradeType;
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

#[Route(path: '/cfdef/grade')]
class LsDefGradeController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefGrade entities.
     */
    #[Route(path: '/', name: 'lsdef_grade_index', methods: ['GET'])]
    public function index(): Response
    {
        $em = $this->managerRegistry->getManager();

        $lsDefGrades = $em->getRepository(LsDefGrade::class)->findBy([], null, 100);

        return $this->render('framework/ls_def_grade/index.html.twig', ['lsDefGrades' => $lsDefGrades]);
    }

    /**
     * Creates a new LsDefGrade entity.
     */
    #[Route(path: '/new', name: 'lsdef_grade_new', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function new(Request $request): Response
    {
        $lsDefGrade = new LsDefGrade();
        $form = $this->createForm(LsDefGradeType::class, $lsDefGrade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddGradeCommand($lsDefGrade);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_grade_show', ['id' => $lsDefGrade->getId()]);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding grade: '.$e->getMessage()));
            }
        }

        return $this->render('framework/ls_def_grade/new.html.twig', [
            'lsDefGrade' => $lsDefGrade,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a LsDefGrade entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_grade_show', methods: ['GET'])]
    public function show(LsDefGrade $lsDefGrade): Response
    {
        $deleteForm = $this->createDeleteForm($lsDefGrade);

        return $this->render('framework/ls_def_grade/show.html.twig', [
            'lsDefGrade' => $lsDefGrade,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing LsDefGrade entity.
     */
    #[Route(path: '/{id}/edit', name: 'lsdef_grade_edit', methods: ['GET', 'POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function edit(Request $request, LsDefGrade $lsDefGrade): Response
    {
        $deleteForm = $this->createDeleteForm($lsDefGrade);
        $editForm = $this->createForm(LsDefGradeType::class, $lsDefGrade);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateGradeCommand($lsDefGrade);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_grade_edit', ['id' => $lsDefGrade->getId()]);
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating grade: '.$e->getMessage()));
            }
        }

        return $this->render('framework/ls_def_grade/edit.html.twig', [
            'lsDefGrade' => $lsDefGrade,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a LsDefGrade entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_grade_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function delete(Request $request, LsDefGrade $lsDefGrade): RedirectResponse
    {
        $form = $this->createDeleteForm($lsDefGrade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteGradeCommand($lsDefGrade);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_grade_index');
    }

    /**
     * Creates a form to delete a LsDefGrade entity.
     */
    private function createDeleteForm(LsDefGrade $lsDefGrade): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_grade_delete', ['id' => $lsDefGrade->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
