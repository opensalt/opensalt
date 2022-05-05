<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddConceptCommand;
use App\Command\Framework\DeleteConceptCommand;
use App\Command\Framework\UpdateConceptCommand;
use App\Entity\Framework\LsDefConcept;
use App\Form\Type\LsDefConceptType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LsDefConcept controller.
 *
 * @Route("/cfdef/concept")
 */
class LsDefConceptController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefConcept entities.
     *
     * @Route("/", methods={"GET"}, name="lsdef_concept_index")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->managerRegistry->getManager();

        $lsDefConcepts = $em->getRepository(LsDefConcept::class)->findBy([], null, 100);

        return [
            'lsDefConcepts' => $lsDefConcepts,
        ];
    }

    /**
     * Creates a new LsDefConcept entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="lsdef_concept_new")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    public function newAction(Request $request)
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

        return [
            'lsDefConcept' => $lsDefConcept,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefConcept entity.
     *
     * @Route("/{id}", methods={"GET"}, name="lsdef_concept_show")
     * @Template()
     */
    public function showAction(LsDefConcept $lsDefConcept): array
    {
        $deleteForm = $this->createDeleteForm($lsDefConcept);

        return [
            'lsDefConcept' => $lsDefConcept,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefConcept entity.
     *
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="lsdef_concept_edit")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, LsDefConcept $lsDefConcept)
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

        return [
            'lsDefConcept' => $lsDefConcept,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefConcept entity.
     *
     * @Route("/{id}", methods={"DELETE"}, name="lsdef_concept_delete")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, LsDefConcept $lsDefConcept): Response
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
     *
     * @param LsDefConcept $lsDefConcept The LsDefConcept entity
     *
     * @return FormInterface The form
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
