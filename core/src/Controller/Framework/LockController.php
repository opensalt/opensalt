<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\LockDocumentCommand;
use App\Command\Framework\LockItemCommand;
use App\Command\Framework\UnlockDocumentCommand;
use App\Command\Framework\UnlockItemCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LockController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/cfdoc/{id}/unlock', name: 'lsdoc_unlock', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'lsDoc')]
    public function releaseDocLock(LsDoc $lsDoc, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $command = new UnlockDocumentCommand($lsDoc, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage());
        }

        return new JsonResponse('OK');
    }

    #[Route(path: '/cfdoc/{id}/lock', name: 'lsdoc_lock', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'lsDoc')]
    public function extendDocLock(LsDoc $lsDoc, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $command = new LockDocumentCommand($lsDoc, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse('OK');
    }

    #[Route(path: '/cfitem/{id}/unlock', name: 'lsitem_unlock', methods: ['POST'])]
    #[IsGranted(Permission::ITEM_EDIT, 'item')]
    public function releaseItemLock(LsItem $item, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $command = new UnlockItemCommand($item, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage());
        }

        return new JsonResponse('OK');
    }

    #[Route(path: '/cfitem/{id}/lock', name: 'lsitem_lock', methods: ['POST'])]
    #[IsGranted(Permission::ITEM_EDIT, 'item')]
    public function extendItemLock(LsItem $item, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $command = new LockItemCommand($item, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse('OK');
    }
}
