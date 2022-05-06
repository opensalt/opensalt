<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddSubjectCommand;
use App\Command\Framework\DeleteSubjectCommand;
use App\Command\Framework\UpdateSubjectCommand;
use App\Entity\Framework\LsDefSubject;
use App\Form\Type\LsDefSubjectType;
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
 * LsDefSubject controller.
 */
#[Route(path: '/cfdef/subject')]
class LsDefSubjectController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefSubject entities.
     *
     * @Template()
     *
     * @return array
     */
    #[Route(path: '/', methods: ['GET'], name: 'lsdef_subject_index')]
    public function indexAction()
    {
        $em = $this->managerRegistry->getManager();

        $lsDefSubjects = $em->getRepository(LsDefSubject::class)->findBy([], null, 100);

        return [
            'lsDefSubjects' => $lsDefSubjects,
        ];
    }

    /**
     * Lists all LsDefSubject entities.
     *
     * @Template()
     *
     * @return array
     */
    #[Route(path: '/list.{_format}', methods: ['GET'], defaults: ['_format' => 'json'], name: 'lsdef_subject_index_json')]
    public function jsonListAction(Request $request)
    {
        // ?page_limit=N&q=SEARCHTEXT
        $em = $this->managerRegistry->getManager();

        $search = $request->query->get('q');
        $objects = $em->getRepository(LsDefSubject::class)->getList($search);

        return [
            'objects' => $objects,
        ];
    }

    /**
     * Creates a new LsDefSubject entity.
     *
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/new', methods: ['GET', 'POST'], name: 'lsdef_subject_new')]
    public function newAction(Request $request)
    {
        $lsDefSubject = new LsDefSubject();
        $form = $this->createForm(LsDefSubjectType::class, $lsDefSubject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddSubjectCommand($lsDefSubject);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_subject_show', ['id' => $lsDefSubject->getId()]);
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
     * @Template()
     *
     * @return array
     */
    #[Route(path: '/{id}', methods: ['GET'], name: 'lsdef_subject_show')]
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
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{id}/edit', methods: ['GET', 'POST'], name: 'lsdef_subject_edit')]
    public function editAction(Request $request, LsDefSubject $lsDefSubject)
    {
        $deleteForm = $this->createDeleteForm($lsDefSubject);
        $editForm = $this->createForm(LsDefSubjectType::class, $lsDefSubject);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateSubjectCommand($lsDefSubject);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_subject_edit', ['id' => $lsDefSubject->getId()]);
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
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return RedirectResponse
     */
    #[Route(path: '/{id}', methods: ['DELETE'], name: 'lsdef_subject_delete')]
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
     * @return FormInterface The form
     */
    private function createDeleteForm(LsDefSubject $lsDefSubject): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_subject_delete', ['id' => $lsDefSubject->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
