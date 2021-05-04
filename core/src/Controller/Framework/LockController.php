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
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;

class LockController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/cfdoc/{id}/unlock", methods={"POST"}, name="lsdoc_unlock")
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param User $user
     */
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
     * @Route("/cfdoc/{id}/lock", methods={"POST"}, name="lsdoc_lock")
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param User $user
     */
    public function extendDocLock(LsDoc $lsDoc, UserInterface $user): JsonResponse
    {
        try {
            $command = new LockDocumentCommand($lsDoc, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 422);
        }

        return new JsonResponse('OK');
    }

    /**
     * @Route("/cfitem/{id}/unlock", methods={"POST"}, name="lsitem_unlock")
     * @Security("is_granted('edit', item)")
     *
     * @param User $user
     */
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
     * @Route("/cfitem/{id}/lock", methods={"POST"}, name="lsitem_lock")
     * @Security("is_granted('edit', item)")
     *
     * @param User $user
     */
    public function extendItemLock(LsItem $item, UserInterface $user): JsonResponse
    {
        try {
            $command = new LockItemCommand($item, $user);
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), 422);
        }

        return new JsonResponse('OK');
    }
}
