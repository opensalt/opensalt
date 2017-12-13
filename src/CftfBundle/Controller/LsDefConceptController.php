<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcher;
use App\Command\Framework\AddConceptCommand;
use App\Command\Framework\DeleteConceptCommand;
use App\Command\Framework\UpdateConceptCommand;
use CftfBundle\Form\Type\LsDefConceptType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDefConcept;
use Symfony\Component\HttpFoundation\Response;

/**
 * LsDefConcept controller.
 *
 * @Route("/cfdef/concept")
 */
class LsDefConceptController extends Controller
{
    use CommandDispatcher;

    /**
     * Lists all LsDefConcept entities.
     *
     * @Route("/", name="lsdef_concept_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefConcepts = $em->getRepository(LsDefConcept::class)->findAll();

        return [
            'lsDefConcepts' => $lsDefConcepts,
        ];
    }

    /**
     * Creates a new LsDefConcept entity.
     *
     * @Route("/new", name="lsdef_concept_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
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

                return $this->redirectToRoute('lsdef_concept_show', array('id' => $lsDefConcept->getId()));
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
     * @Route("/{id}", name="lsdef_concept_show")
     * @Method("GET")
     * @Template()
     *
     * @param LsDefConcept $lsDefConcept
     *
     * @return array
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
     * @Route("/{id}/edit", name="lsdef_concept_edit")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsDefConcept $lsDefConcept
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
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

                return $this->redirectToRoute('lsdef_concept_edit', array('id' => $lsDefConcept->getId()));
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
     * @Route("/{id}", name="lsdef_concept_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param LsDefConcept $lsDefConcept
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
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
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(LsDefConcept $lsDefConcept): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_concept_delete', array('id' => $lsDefConcept->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
