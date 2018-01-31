<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddItemTypeCommand;
use App\Command\Framework\DeleteItemTypeCommand;
use App\Command\Framework\UpdateItemTypeCommand;
use CftfBundle\Form\Type\LsDefItemTypeType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDefItemType;

/**
 * LsDefItemType controller.
 *
 * @Route("/cfdef/item_type")
 */
class LsDefItemTypeController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsDefItemType entities.
     *
     * @Route("/", name="lsdef_item_type_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefItemTypes = $em->getRepository(LsDefItemType::class)->findAll();

        return [
            'lsDefItemTypes' => $lsDefItemTypes,
        ];
    }

    /**
     * Lists all LsDefItemType entities.
     *
     * @Route("/list.{_format}", defaults={"_format"="json"}, name="lsdef_item_type_index_json")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function jsonListAction(Request $request)
    {
        // ?page_limit=N&q=SEARCHTEXT
        $em = $this->getDoctrine()->getManager();

        $search = $request->query->get('q', null);
        $page = $request->query->get('page', 1);
        $page_limit = $request->query->get('page_limit', 50);

        $results = $em->getRepository(LsDefItemType::class)
            ->getSelect2List($search, $page_limit, $page);

        if (!empty($search) && empty($results['results'][$search])) {
            array_unshift(
                $results['results'],
                ['id' => '__'.$search, 'title' => '(NEW) '.$search]
            );
        }

        return [
            'results' => $results['results'],
            'more' => $results['more'],
        ];
    }

    /**
     * Creates a new LsDefItemType entity.
     *
     * @Route("/new", name="lsdef_item_type_new")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $lsDefItemType = new LsDefItemType();
        $form = $this->createForm(LsDefItemTypeType::class, $lsDefItemType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddItemTypeCommand($lsDefItemType);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_item_type_show', array('id' => $lsDefItemType->getId()));
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding item type: '.$e->getMessage()));
            }
        }

        return [
            'lsDefItemType' => $lsDefItemType,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefItemType entity.
     *
     * @Route("/{id}", name="lsdef_item_type_show")
     * @Method("GET")
     * @Template()
     *
     * @param LsDefItemType $lsDefItemType
     *
     * @return array
     */
    public function showAction(LsDefItemType $lsDefItemType)
    {
        $deleteForm = $this->createDeleteForm($lsDefItemType);

        return [
            'lsDefItemType' => $lsDefItemType,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefItemType entity.
     *
     * @Route("/{id}/edit", name="lsdef_item_type_edit")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param LsDefItemType $lsDefItemType
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsDefItemType $lsDefItemType)
    {
        $deleteForm = $this->createDeleteForm($lsDefItemType);
        $editForm = $this->createForm(LsDefItemTypeType::class, $lsDefItemType);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateItemTypeCommand($lsDefItemType);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_item_type_edit', array('id' => $lsDefItemType->getId()));
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating concept: '.$e->getMessage()));
            }
        }

        return [
            'lsDefItemType' => $lsDefItemType,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefItemType entity.
     *
     * @Route("/{id}", name="lsdef_item_type_delete")
     * @Method("DELETE")
     *
     * @param Request $request
     * @param LsDefItemType $lsDefItemType
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsDefItemType $lsDefItemType)
    {
        $form = $this->createDeleteForm($lsDefItemType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteItemTypeCommand($lsDefItemType);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_item_type_index');
    }

    /**
     * Creates a form to delete a LsDefItemType entity.
     *
     * @param LsDefItemType $lsDefItemType The LsDefItemType entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsDefItemType $lsDefItemType)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_item_type_delete', array('id' => $lsDefItemType->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
