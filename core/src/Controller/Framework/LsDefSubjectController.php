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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/cfdef/subject')]
class LsDefSubjectController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefSubject entities.
     */
    #[Route(path: '/', name: 'lsdef_subject_index', methods: ['GET'])]
    public function index(): Response
    {
        $em = $this->managerRegistry->getManager();

        $lsDefSubjects = $em->getRepository(LsDefSubject::class)->findBy([], null, 100);

        return $this->render('framework/ls_def_subject/index.html.twig', [
            'lsDefSubjects' => $lsDefSubjects,
        ]);
    }

    /**
     * Lists all LsDefSubject entities.
     */
    #[Route(path: '/list.{_format}', name: 'lsdef_subject_index_json', defaults: ['_format' => 'json'], methods: ['GET'])]
    public function jsonList(Request $request, string $_format = 'json'): Response
    {
        // ?page_limit=N&q=SEARCHTEXT
        $em = $this->managerRegistry->getManager();

        $search = $request->query->get('q');
        $objects = $em->getRepository(LsDefSubject::class)->getList($search);

        return $this->render('framework/ls_def_subject/json_list.'.$_format.'.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * Creates a new LsDefSubject entity.
     */
    #[Route(path: '/new', name: 'lsdef_subject_new', methods: ['GET', 'POST'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function new(Request $request): Response
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

        return $this->render('framework/ls_def_subject/new.html.twig', [
            'lsDefSubject' => $lsDefSubject,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a LsDefSubject entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_subject_show', methods: ['GET'])]
    public function show(LsDefSubject $lsDefSubject): Response
    {
        $deleteForm = $this->createDeleteForm($lsDefSubject);

        return $this->render('framework/ls_def_subject/show.html.twig', [
            'lsDefSubject' => $lsDefSubject,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing LsDefSubject entity.
     */
    #[Route(path: '/{id}/edit', name: 'lsdef_subject_edit', methods: ['GET', 'POST'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function edit(Request $request, LsDefSubject $lsDefSubject): Response
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

        return $this->render('framework/ls_def_subject/edit.html.twig', [
            'lsDefSubject' => $lsDefSubject,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Deletes a LsDefSubject entity.
     */
    #[Route(path: '/{id}', name: 'lsdef_subject_delete', methods: ['DELETE'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function delete(Request $request, LsDefSubject $lsDefSubject): RedirectResponse
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
