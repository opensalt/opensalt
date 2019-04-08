<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddLicenceCommand;
use App\Command\Framework\DeleteLicenceCommand;
use App\Command\Framework\UpdateLicenceCommand;
use App\Form\Type\LsDefLicenceType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Framework\LsDefLicence;

/**
 * LsDefLicence controller.
 *
 * @Route("/cfdef/licence")
 */
class LsDefLicenceController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsDefLicence entities.
     *
     * @Route("/", methods={"GET"}, name="lsdef_licence_index")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsDefLicences = $em->getRepository(LsDefLicence::class)->findAll();

        return [
            'lsDefLicences' => $lsDefLicences,
        ];
    }

    /**
     * Lists all LsDefLicence entities.
     *
     * @Route("/list.{_format}", methods={"GET"}, defaults={"_format"="json"}, name="lsdef_licence_index_json")
     * @Template()
     */
    public function jsonListAction(): array
    {
        $em = $this->getDoctrine()->getManager();

        $objects = $em->getRepository(LsDefLicence::class)->getList();

        return [
            'objects' => $objects,
        ];
    }

    /**
     * Creates a new LsDefLicence entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="lsdef_licence_new")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $lsDefLicence = new LsDefLicence();
        $form = $this->createForm(LsDefLicenceType::class, $lsDefLicence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddLicenceCommand($lsDefLicence);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_licence_show', array('id' => $lsDefLicence->getId()));
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding licence: '.$e->getMessage()));
            }
        }

        return [
            'lsDefLicence' => $lsDefLicence,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDefLicence entity.
     *
     * @Route("/{id}", methods={"GET"}, name="lsdef_licence_show")
     * @Template()
     *
     * @param LsDefLicence $lsDefLicence
     *
     * @return array
     */
    public function showAction(LsDefLicence $lsDefLicence)
    {
        $deleteForm = $this->createDeleteForm($lsDefLicence);

        return [
            'lsDefLicence' => $lsDefLicence,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsDefLicence entity.
     *
     * @Route("/{id}/edit", methods={"GET", "POST"}, name="lsdef_licence_edit")
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @param Request $request
     * @param LsDefLicence $lsDefLicence
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, LsDefLicence $lsDefLicence)
    {
        $deleteForm = $this->createDeleteForm($lsDefLicence);
        $editForm = $this->createForm(LsDefLicenceType::class, $lsDefLicence);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateLicenceCommand($lsDefLicence);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_licence_edit', array('id' => $lsDefLicence->getId()));
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating licence: '.$e->getMessage()));
            }
        }

        return [
            'lsDefLicence' => $lsDefLicence,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Deletes a LsDefLicence entity.
     *
     * @Route("/{id}", methods={"DELETE"}, name="lsdef_licence_delete")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @param Request $request
     * @param LsDefLicence $lsDefLicence
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsDefLicence $lsDefLicence)
    {
        $form = $this->createDeleteForm($lsDefLicence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteLicenceCommand($lsDefLicence);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdef_licence_index');
    }

    /**
     * Creates a form to delete a LsDefLicence entity.
     *
     * @param LsDefLicence $lsDefLicence The LsDefLicence entity
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(LsDefLicence $lsDefLicence): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_licence_delete', array('id' => $lsDefLicence->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
