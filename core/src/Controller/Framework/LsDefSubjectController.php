<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddSubjectCommand;
use App\Command\Framework\DeleteSubjectCommand;
use App\Command\Framework\UpdateSubjectCommand;
use App\Form\Type\LsDefSubjectType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Framework\LsDefSubject;

/**
 * LsDefSubject controller.
 *
 * @Route("/cfdef/subject")
 */
class LsDefSubjectController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsDefSubject entities.
     *
     * @Route("/", methods={"GET"}, name="lsdef_subject_index")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefSubjects = $em->getRepository(LsDefSubject::class)->findAll();

        return [
            'lsDefSubjects' => $lsDefSubjects,
        ];
    }

    /**
     * Lists all LsDefSubject entities.
     *
     * @Route("/list.{_format}", methods={"GET"}, defaults={"_format"="json"}, name="lsdef_subject_index_json")
     * @Template()
     *
     * @return array
     */
    public function jsonListAction(Request $request)
    {
        // ?page_limit=N&q=SEARCHTEXT
        $em = $this->getDoctrine()->getManager();

        $search = $request->query->get('q');
        $objects = $em->getRepository(LsDefSubject::class)->getList($search);

        return [
            'objects' => $objects,
        ];
    }

    /**
     * Creates a new LsDefSubject entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="lsdef_subject_new")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $lsDefSubject = new LsDefSubject();
        $form = $this->createForm(LsDefSubjectType::class, $lsDefSubject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddSubjectCommand($lsDefSubject);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_subject_show', array('id' => $lsDefSubject->getId()));
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding subject: '.$e->getMessage()));
            }
        }

        return [
            'lsDefSubject' => $lsDefSubject,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefSubject entity.
     *
     * @Route("/{id}", methods={"GET"}, name="lsdef_subject_show")
     * @Template()
     *
     * @return array
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
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="lsdef_subject_edit")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsDefSubject $lsDefSubject)
    {
        $deleteForm = $this->createDeleteForm($lsDefSubject);
        $editForm = $this->createForm(LsDefSubjectType::class, $lsDefSubject);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateSubjectCommand($lsDefSubject);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_subject_edit', array('id' => $lsDefSubject->getId()));
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating subject: '.$e->getMessage()));
            }
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
     * @Route("/{id}", methods={"DELETE"}, name="lsdef_subject_delete")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsDefSubject $lsDefSubject)
    {
        $form = $this->createDeleteForm($lsDefSubject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteSubjectCommand($lsDefSubject);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_subject_index');
    }

    /**
     * Creates a form to delete a LsDefSubject entity.
     *
     * @param LsDefSubject $lsDefSubject The LsDefSubject entity
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(LsDefSubject $lsDefSubject): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_subject_delete', array('id' => $lsDefSubject->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
