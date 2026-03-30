<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddTreeAssociationCommand;
use App\Command\Framework\DeleteAssociationCommand;
use App\Command\Framework\UpdateAssociationCommand;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/framework/editor')]
class AssociationController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/association/new/{identifier}', name: 'editor_association_new', methods: ['POST'])]
    #[IsGranted(Permission::ASSOCIATION_ADD_TO, 'lsDoc')]
    public function newAssociation(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDoc $lsDoc,
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return new JsonResponse(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new AddTreeAssociationCommand(
                $lsDoc,
                $data['origin'] ?? null,
                $data['type'] ?? null,
                $data['dest'] ?? null,
                $data['assocGroup'] ?? null,
                $data['annotation'] ?? null,
                $data['extensions'] ?? null
            );
            $this->sendCommand($command);
            $lsAssociation = $command->getAssociation();

            return new JsonResponse([
                'id' => $lsAssociation?->getId(),
                'identifier' => $lsAssociation?->getIdentifier(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/association/{identifier}', name: 'editor_association_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(Permission::ASSOCIATION_EDIT, 'lsAssociation')]
    public function updateAssociation(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsAssociation $lsAssociation,
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (null !== $data) {
            if (isset($data['type'])) {
                $lsAssociation->setType($data['type']);
            }
            if (isset($data['annotation'])) {
                $lsAssociation->setNotes($data['annotation']);
            }
            if (isset($data['sequenceNumber'])) {
                $lsAssociation->setSequenceNumber((int) $data['sequenceNumber']);
            }
            // assocGroup might be complex if it's an entity,
            // but for now let's assume we handle it via the group's identifier if needed.
        }

        try {
            $command = new UpdateAssociationCommand($lsAssociation);
            $this->sendCommand($command);

            return new JsonResponse([
                'id' => $lsAssociation->getId(),
                'identifier' => $lsAssociation->getIdentifier(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/association/{identifier}', name: 'editor_association_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::ASSOCIATION_EDIT, 'lsAssociation')]
    public function deleteAssociation(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsAssociation $lsAssociation,
    ): Response {
        try {
            $command = new DeleteAssociationCommand($lsAssociation);
            $this->sendCommand($command);

            return new JsonResponse(['status' => 'OK'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
