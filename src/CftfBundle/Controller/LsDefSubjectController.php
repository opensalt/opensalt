<?php

namespace CftfBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Form\LsDefSubjectType;

/**
 * LsDefSubject controller.
 *
 * @Route("/lsdef/subject")
 */
class LsDefSubjectController extends Controller
{
    /**
     * Lists all LsDefSubject entities.
     *
     * @Route("/", name="lsdef_subject_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefSubjects = $em->getRepository('CftfBundle:LsDefSubject')->findAll();

        return [
            'lsDefSubjects' => $lsDefSubjects,
        ];
    }

    /**
     * Lists all LsDefSubject entities.
     *
     * @Route("/list.{_format}", defaults={"_format"="json"}, name="lsdef_subject_index_json")
     * @Method("GET")
     * @Template()
     */
    public function jsonListAction(Request $request)
    {
        // ?page_limit=N&q=SEARCHTEXT
        $em = $this->getDoctrine()->getManager();

        $objects = $em->getRepository('CftfBundle:LsDefSubject')->getList();

//        $want = $request->query->get('q');
//        if (!array_key_exists($want, $lsDefItemTypes)) {
//        }

        return [
            'objects' => $objects,
        ];
    }

    /**
     * Creates a new LsDefSubject entity.
     *
     * @Route("/new", name="lsdef_subject_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request)
    {
        $lsDefSubject = new LsDefSubject();
        $form = $this->createForm('CftfBundle\Form\LsDefSubjectType', $lsDefSubject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefSubject);
            $em->flush();

            return $this->redirectToRoute('lsdef_subject_show', array('id' => $lsDefSubject->getId()));
        }

        return [
            'lsDefSubject' => $lsDefSubject,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefSubject entity.
     *
     * @Route("/{id}", name="lsdef_subject_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(LsDefSubject $lsDefSubject)
    {
        $deleteForm = $this->createDeleteForm($lsDefSubject);

        return [
            'lsDefSubject' => $lsDefSubject,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefSubject entity.
     *
     * @Route("/{id}/edit", name="lsdef_subject_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, LsDefSubject $lsDefSubject)
    {
        $deleteForm = $this->createDeleteForm($lsDefSubject);
        $editForm = $this->createForm('CftfBundle\Form\LsDefSubjectType', $lsDefSubject);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefSubject);
            $em->flush();

            return $this->redirectToRoute('lsdef_subject_edit', array('id' => $lsDefSubject->getId()));
        }

        return [
            'lsDefSubject' => $lsDefSubject,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefSubject entity.
     *
     * @Route("/{id}", name="lsdef_subject_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, LsDefSubject $lsDefSubject)
    {
        $form = $this->createDeleteForm($lsDefSubject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($lsDefSubject);
            $em->flush();
        }

        return $this->redirectToRoute('lsdef_subject_index');
    }

    /**
     * Creates a form to delete a LsDefSubject entity.
     *
     * @param LsDefSubject $lsDefSubject The LsDefSubject entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsDefSubject $lsDefSubject)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_subject_delete', array('id' => $lsDefSubject->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
