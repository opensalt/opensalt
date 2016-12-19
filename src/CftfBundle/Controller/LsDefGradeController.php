<?php

namespace CftfBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDefGrade;

/**
 * LsDefGrade controller.
 *
 * @Route("/lsdef/grade")
 */
class LsDefGradeController extends Controller
{
    /**
     * Lists all LsDefGrade entities.
     *
     * @Route("/", name="lsdef_grade_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefGrades = $em->getRepository('CftfBundle:LsDefGrade')->findAll();

        return [
            'lsDefGrades' => $lsDefGrades,
        ];
    }

    /**
     * Creates a new LsDefGrade entity.
     *
     * @Route("/new", name="lsdef_grade_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request)
    {
        $lsDefGrade = new LsDefGrade();
        $form = $this->createForm('CftfBundle\Form\LsDefGradeType', $lsDefGrade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefGrade);
            $em->flush();

            return $this->redirectToRoute('lsdef_grade_show', array('id' => $lsDefGrade->getId()));
        }

        return [
            'lsDefGrade' => $lsDefGrade,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefGrade entity.
     *
     * @Route("/{id}", name="lsdef_grade_show")
     * @Method("GET")
     * @Template()
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
     * @Route("/{id}/edit", name="lsdef_grade_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, LsDefGrade $lsDefGrade)
    {
        $deleteForm = $this->createDeleteForm($lsDefGrade);
        $editForm = $this->createForm('CftfBundle\Form\LsDefGradeType', $lsDefGrade);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefGrade);
            $em->flush();

            return $this->redirectToRoute('lsdef_grade_edit', array('id' => $lsDefGrade->getId()));
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
     * @Route("/{id}", name="lsdef_grade_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, LsDefGrade $lsDefGrade)
    {
        $form = $this->createDeleteForm($lsDefGrade);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($lsDefGrade);
            $em->flush();
        }

        return $this->redirectToRoute('lsdef_grade_index');
    }

    /**
     * Creates a form to delete a LsDefGrade entity.
     *
     * @param LsDefGrade $lsDefGrade The LsDefGrade entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsDefGrade $lsDefGrade)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_grade_delete', array('id' => $lsDefGrade->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
