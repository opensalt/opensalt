<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddGradeCommand;
use App\Command\Framework\DeleteGradeCommand;
use App\Command\Framework\UpdateGradeCommand;
use App\Form\Type\LsDefGradeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Framework\LsDefGrade;

/**
 * LsDefGrade controller.
 *
 * @Route("/cfdef/grade")
 */
class LsDefGradeController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsDefGrade entities.
     *
     * @Route("/", methods={"GET"}, name="lsdef_grade_index")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefGrades = $em->getRepository(LsDefGrade::class)->findAll();

        return [
            'lsDefGrades' => $lsDefGrades,
        ];
    }

    /**
     * Creates a new LsDefGrade entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="lsdef_grade_new")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $lsDefGrade = new LsDefGrade();
        $form = $this->createForm(LsDefGradeType::class, $lsDefGrade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddGradeCommand($lsDefGrade);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_grade_show', array('id' => $lsDefGrade->getId()));
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
     * @Route("/{id}", methods={"GET"}, name="lsdef_grade_show")
     * @Template()
     *
     * @return array
     */
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
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="lsdef_grade_edit")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsDefGrade $lsDefGrade)
    {
        $deleteForm = $this->createDeleteForm($lsDefGrade);
        $editForm = $this->createForm(LsDefGradeType::class, $lsDefGrade);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateGradeCommand($lsDefGrade);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_grade_edit', array('id' => $lsDefGrade->getId()));
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
     * @Route("/{id}", methods={"DELETE"}, name="lsdef_grade_delete")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
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
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(LsDefGrade $lsDefGrade): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_grade_delete', array('id' => $lsDefGrade->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
