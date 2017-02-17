<?php

namespace CftfBundle\Controller;

use CftfBundle\Form\Type\LsDefItemTypeType;
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

        $lsDefItemTypes = $em->getRepository('CftfBundle:LsDefItemType')->findAll();

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

        $objects = $em->getRepository('CftfBundle:LsDefItemType')->getList();

//        $want = $request->query->get('q');
//        if (!array_key_exists($want, $objects)) {
//        }

        return [
            'objects' => $objects,
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
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefItemType);
            $em->flush();

            return $this->redirectToRoute('lsdef_item_type_show', array('id' => $lsDefItemType->getId()));
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
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDefItemType);
            $em->flush();

            return $this->redirectToRoute('lsdef_item_type_edit', array('id' => $lsDefItemType->getId()));
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
            $em = $this->getDoctrine()->getManager();
            $em->remove($lsDefItemType);
            $em->flush();
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
