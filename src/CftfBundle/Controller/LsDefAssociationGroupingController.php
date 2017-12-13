<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcher;
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
    use CommandDispatcher;

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

        $lsDefAssociationGroupings = $em->getRepository('CftfBundle:LsDefAssociationGrouping')->findAll();

        return [
            'lsDefAssociationGroupings' => $lsDefAssociationGroupings,
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
    public function newAction(Request $request): Response
    {
        $ajax = $request->isXmlHttpRequest();

        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $form = $this->createForm(LsDefAssociationGroupingType::class, $lsDefAssociationGrouping);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddAssociationGroupCommand($lsDefAssociationGrouping);
                $this->sendCommand($command);

                // if ajax request, just return the created id
                if ($ajax) {
                    return new Response($lsDefAssociationGrouping->getId(), Response::HTTP_CREATED);
                }

                return $this->redirectToRoute('lsdef_association_grouping_show', array('id' => $lsDefAssociationGrouping->getId()));
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new association group: '.$e->getMessage()));
            }
        }

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render('CftfBundle:LsDefAssociationGrouping:new.html.twig', ['form' => $form->createView()], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return [
            'lsDefAssociationGrouping' => $lsDefAssociationGrouping,
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
     * @param LsDefAssociationGrouping $lsDefAssociationGrouping
     *
     * @return array
     */
    public function showAction(LsDefAssociationGrouping $lsDefAssociationGrouping)
    {
        $deleteForm = $this->createDeleteForm($lsDefAssociationGrouping);

        return [
            'lsDefAssociationGrouping' => $lsDefAssociationGrouping,
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
     * @param LsDefAssociationGrouping $lsDefAssociationGrouping
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsDefAssociationGrouping $lsDefAssociationGrouping)
    {
        $deleteForm = $this->createDeleteForm($lsDefAssociationGrouping);
        $editForm = $this->createForm(LsDefAssociationGroupingType::class, $lsDefAssociationGrouping);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateAssociationGroupCommand($lsDefAssociationGrouping);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_association_grouping_edit', array('id' => $lsDefAssociationGrouping->getId()));
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating association group: '.$e->getMessage()));
            }
        }

        return [
            'lsDefAssociationGrouping' => $lsDefAssociationGrouping,
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
     * @param LsDefAssociationGrouping $lsDefAssociationGrouping
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsDefAssociationGrouping $lsDefAssociationGrouping)
    {
        $form = $this->createDeleteForm($lsDefAssociationGrouping);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteAssociationGroupCommand($lsDefAssociationGrouping);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_association_grouping_index');
    }

    /**
     * Creates a form to delete a LsDefAssociationGrouping entity.
     *
     * @param LsDefAssociationGrouping $lsDefAssociationGrouping The LsDefAssociationGrouping entity
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(LsDefAssociationGrouping $lsDefAssociationGrouping): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_association_grouping_delete', array('id' => $lsDefAssociationGrouping->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
