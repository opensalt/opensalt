<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddAssociationGroupCommand;
use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Command\Framework\UpdateAssociationGroupCommand;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Form\Type\LsDefAssociationGroupingType;
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

#[Route(path: '/cfdef/association_grouping')]
class LsDefAssociationGroupingController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDefAssociationGrouping entities.
     */
    #[Route(path: '/', name: 'lsdef_association_grouping_index', methods: ['GET'])]
    #[Template]
    public function indexAction(): array
    {
        $em = $this->managerRegistry->getManager();

        $associationGroupings = $em->getRepository(LsDefAssociationGrouping::class)->findBy([], null, 100);

        return [
            'lsDefAssociationGroupings' => $associationGroupings,
        ];
    }

    /**
     * Creates a new LsDefAssociationGrouping entity.
     */
    #[Route(path: '/new', name: 'lsdef_association_grouping_new', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('create', 'lsdoc')")]
    public function newAction(Request $request): array|Response
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
                    return new Response((string) $associationGrouping->getId(), Response::HTTP_CREATED);
                }

                return $this->redirectToRoute('lsdef_association_grouping_show', ['id' => $associationGrouping->getId()]);
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
     * @return array
     */
    #[Route(path: '/{id}', name: 'lsdef_association_grouping_show', methods: ['GET'])]
    #[Template]
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
     * @return array|RedirectResponse
     */
    #[Route(path: '/{id}/edit', name: 'lsdef_association_grouping_edit', methods: ['GET', 'POST'])]
    #[Template]
    #[Security("is_granted('create', 'lsdoc')")]
    public function editAction(Request $request, LsDefAssociationGrouping $associationGrouping)
    {
        $deleteForm = $this->createDeleteForm($associationGrouping);
        $editForm = $this->createForm(LsDefAssociationGroupingType::class, $associationGrouping);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateAssociationGroupCommand($associationGrouping);
                $this->sendCommand($command);

                return $this->redirectToRoute('lsdef_association_grouping_edit', ['id' => $associationGrouping->getId()]);
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
     * @return RedirectResponse
     */
    #[Route(path: '/{id}', name: 'lsdef_association_grouping_delete', methods: ['DELETE'])]
    #[Security("is_granted('create', 'lsdoc')")]
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
     */
    private function createDeleteForm(LsDefAssociationGrouping $associationGrouping): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsdef_association_grouping_delete', ['id' => $associationGrouping->getId()]))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
