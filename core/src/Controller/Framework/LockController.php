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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class LockController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @param User $user
     */
    #[Route(path: '/cfdoc/{id}/unlock', name: 'lsdoc_unlock', methods: ['POST'])]
    #[Security("is_granted('edit', lsDoc)")]
    public function releaseDocLock(LsDoc $lsDoc, UserInterface $user): JsonResponse
    {
        try {
            $command = new UnlockDocumentCommand($lsDoc, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage());
        }

        return new JsonResponse('OK');
    }

    /**
     * @param User $user
     */
    #[Route(path: '/cfdoc/{id}/lock', name: 'lsdoc_lock', methods: ['POST'])]
    #[Security("is_granted('edit', lsDoc)")]
    public function extendDocLock(LsDoc $lsDoc, UserInterface $user): JsonResponse
    {
        try {
            $command = new LockDocumentCommand($lsDoc, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse('OK');
    }

    /**
     * @param User $user
     */
    #[Route(path: '/cfitem/{id}/unlock', name: 'lsitem_unlock', methods: ['POST'])]
    #[Security("is_granted('edit', item)")]
    public function releaseItemLock(LsItem $item, UserInterface $user): JsonResponse
    {
        try {
            $command = new UnlockItemCommand($item, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage());
        }

        return new JsonResponse('OK');
    }

    /**
     * @param User $user
     */
    #[Route(path: '/cfitem/{id}/lock', name: 'lsitem_lock', methods: ['POST'])]
    #[Security("is_granted('edit', item)")]
    public function extendItemLock(LsItem $item, UserInterface $user): JsonResponse
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
