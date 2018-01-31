<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddAssociationGroupCommand;
use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Command\Framework\UpdateAssociationGroupCommand;
use CftfBundle\Form\Type\LsDefAssociationGroupingType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDefAssociationGrouping;

/**
 * LsDefAssociationGrouping controller.
 *
 * @Route("/cfdef/association_grouping")
 */
class LsDefAssociationGroupingController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsDefAssociationGrouping entities.
     *
     * @Route("/", name="lsdef_association_grouping_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
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
     * @Route("/new", name="lsdef_association_grouping_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
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
            return $this->render('CftfBundle:LsDefAssociationGrouping:new.html.twig', ['form' => $form->createView()], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return [
            'lsDefAssociationGrouping' => $associationGrouping,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefAssociationGrouping entity.
     *
     * @Route("/{id}", name="lsdef_association_grouping_show")
     * @Method("GET")
     * @Template()
     *
     * @param LsDefAssociationGrouping $associationGrouping
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
     * @Route("/{id}/edit", name="lsdef_association_grouping_edit")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsDefAssociationGrouping $associationGrouping
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
     * @Route("/{id}", name="lsdef_association_grouping_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param LsDefAssociationGrouping $associationGrouping
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
