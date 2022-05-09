<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddGradeCommand;
use App\Command\Framework\DeleteGradeCommand;
use App\Command\Framework\UpdateGradeCommand;
use App\Entity\Framework\LsDefGrade;
use App\Form\Type\LsDefGradeType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
     *
     * @return array
     */
    #[Route(path: '/', name: 'lsdef_grade_index', methods: ['GET'])]
    #[Template]
    public function indexAction()
    {
        $em = $this->managerRegistry->getManager();

        $lsDefGrades = $em->getRepository(LsDefGrade::class)->findBy([], null, 100);

        return [
            'lsDefGrades' => $lsDefGrades,
        ];
    }

    /**
     * Creates a new LsDefGrade entity.
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/new', name: 'lsdef_grade_new', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('create', 'lsdoc')")]
    public function newAction(Request $request)
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

        return [
            'lsDefGrade' => $lsDefGrade,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefGrade entity.
     *
     * @return array
     */
    #[Route(path: '/{id}', name: 'lsdef_grade_show', methods: ['GET'])]
    #[Template]
    public function showAction(LsDefGrade $lsDefGrade)
    {
        $deleteForm = $this->createDeleteForm($lsDefGrade);

        return [
            'lsDefGrade' => $lsDefGrade,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefGrade entity.
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{id}/edit', name: 'lsdef_grade_edit', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('create', 'lsdoc')")]
    public function editAction(Request $request, LsDefGrade $lsDefGrade)
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

        return [
            'lsDefGrade' => $lsDefGrade,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefGrade entity.
     *
     * @return RedirectResponse
     */
    #[Route(path: '/{id}', name: 'lsdef_grade_delete', methods: ['DELETE'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function deleteAction(Request $request, LsDefGrade $lsDefGrade)
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
