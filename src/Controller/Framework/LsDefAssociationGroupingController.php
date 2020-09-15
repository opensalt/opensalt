<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddAssociationGroupCommand;
use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Command\Framework\UpdateAssociationGroupCommand;
use App\Form\Type\LsDefAssociationGroupingType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Framework\LsDefAssociationGrouping;

/**
 * LsDefAssociationGrouping controller.
 *
 * @Route("/cfdef/association_grouping")
 */
class LsDefAssociationGroupingController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsDefAssociationGrouping entities.
     *
     * @Route("/", methods={"GET"}, name="lsdef_association_grouping_index")
     * @Template()
     */
    public function indexAction(): array
    {
        $em = $this->getDoctrine()->getManager();

        $associationGroupings = $em->getRepository(LsDefAssociationGrouping::class)->findAll();

        return [
            'lsDefAssociationGroupings' => $associationGroupings,
        ];
    }

    /**
     * Creates a new LsDefAssociationGrouping entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="lsdef_association_grouping_new")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $ajax = $request->isXmlHttpRequest();

        $associationGrouping = new LsDefAssociationGrouping();
        $form = $this->createForm(LsDefAssociationGroupingType::class, $associationGrouping);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddAssociationGroupCommand($associationGrouping);
                $this->sendCommand($command);

                // if ajax request, just return the created id
                if ($ajax) {
                    return new Response($associationGrouping->getId(), Response::HTTP_CREATED);
                }

                return $this->redirectToRoute('lsdef_association_grouping_show', array('id' => $associationGrouping->getId()));
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new association group: '.$e->getMessage()));
            }
        }

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render('framework/ls_def_association_grouping/new.html.twig', ['form' => $form->createView()], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return [
            'lsDefAssociationGrouping' => $associationGrouping,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefAssociationGrouping entity.
     *
     * @Route("/{id}", methods={"GET"}, name="lsdef_association_grouping_show")
     * @Template()
     *
     * @return array
     */
    public function showAction(LsDefAssociationGrouping $associationGrouping)
    {
        $deleteForm = $this->createDeleteForm($associationGrouping);

        return [
            'lsDefAssociationGrouping' => $associationGrouping,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefAssociationGrouping entity.
     *
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="lsdef_association_grouping_edit")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsDefAssociationGrouping $associationGrouping)
    {
        $deleteForm = $this->createDeleteForm($associationGrouping);
        $editForm = $this->createForm(LsDefAssociationGroupingType::class, $associationGrouping);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateAssociationGroupCommand($associationGrouping);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_association_grouping_edit', array('id' => $associationGrouping->getId()));
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating association group: '.$e->getMessage()));
            }
        }

        return [
            'lsDefAssociationGrouping' => $associationGrouping,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefAssociationGrouping entity.
     *
     * @Route("/{id}", methods={"DELETE"}, name="lsdef_association_grouping_delete")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsDefAssociationGrouping $associationGrouping)
    {
        $form = $this->createDeleteForm($associationGrouping);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteAssociationGroupCommand($associationGrouping);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_association_grouping_index');
    }

    /**
     * Creates a form to delete a LsDefAssociationGrouping entity.
     *
     * @param LsDefAssociationGrouping $associationGrouping The LsDefAssociationGrouping entity
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(LsDefAssociationGrouping $associationGrouping): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_association_grouping_delete', array('id' => $associationGrouping->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
