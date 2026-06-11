<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddAssociationGroupCommand;
use App\Command\Framework\DeleteAssociationGroupCommand;
use App\Command\Framework\UpdateAssociationGroupCommand;
use App\Entity\Framework\LsDefAssociationGrouping;
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
class AssociationGroupController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/association_grouping/new/{identifier}', name: 'editor_association_grouping_new', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'lsDoc')]
    public function newGroup(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDoc $lsDoc,
    ): Response {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $group = new LsDefAssociationGrouping();
        $group->setLsDoc($lsDoc);
        if (isset($data['title'])) {
            $group->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $group->setDescription($data['description']);
        }

        try {
            $command = new AddAssociationGroupCommand($group);
            $this->sendCommand($command);

            return new JsonResponse([
                'id' => $group->getId(),
                'identifier' => $group->getIdentifier(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/association_grouping/{identifier}', name: 'editor_association_grouping_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function updateGroup(
        Request $request,
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDefAssociationGrouping $group,
    ): Response {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if (null !== $data) {
            if (isset($data['title'])) {
                $group->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $group->setDescription($data['description']);
            }
        }

        try {
            $command = new UpdateAssociationGroupCommand($group);
            $this->sendCommand($command);

            return new JsonResponse(['status' => 'OK'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/association_grouping/{identifier}', name: 'editor_association_grouping_delete', methods: ['DELETE'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function deleteGroup(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDefAssociationGrouping $group,
    ): Response {
        try {
            $command = new DeleteAssociationGroupCommand($group);
            $this->sendCommand($command);

            return new JsonResponse(['status' => 'OK'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
