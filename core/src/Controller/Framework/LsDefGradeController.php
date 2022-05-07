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

/**
 * LsDefGrade controller.
 */
#[Route(path: '/cfdef/grade')]
class LsDefGradeController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefGrade entities.
     *
     * @Template()
     *
     * @return array
     */
    #[Route(path: '/', methods: ['GET'], name: 'lsdef_grade_index')]
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
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/new', methods: ['GET', 'POST'], name: 'lsdef_grade_new')]
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
     * @Template()
     *
     * @return array
     */
    #[Route(path: '/{id}', methods: ['GET'], name: 'lsdef_grade_show')]
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
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{id}/edit', methods: ['GET', 'POST'], name: 'lsdef_grade_edit')]
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
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return RedirectResponse
     */
    #[Route(path: '/{id}', methods: ['DELETE'], name: 'lsdef_grade_delete')]
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
     *
     * @param LsDefGrade $lsDefGrade The LsDefGrade entity
     *
     * @return FormInterface The form
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
