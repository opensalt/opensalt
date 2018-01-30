<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\LockDocumentCommand;
use App\Command\Framework\LockItemCommand;
use App\Command\Framework\UnlockDocumentCommand;
use App\Command\Framework\UnlockItemCommand;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;

class LockController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        // event_dispatcher
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route("/cfdoc/{id}/unlock", name="lsdoc_unlock")
     * @Method({"POST"})
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param LsDoc $lsDoc
     * @param User $user
     *
     * @return JsonResponse
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
     * @Route("/cfdoc/{id}/lock", name="lsdoc_lock")
     * @Method({"POST"})
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param LsDoc $lsDoc
     * @param User $user
     *
     * @return JsonResponse
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
     * @Route("/cfitem/{id}/unlock", name="lsitem_unlock")
     * @Method({"POST"})
     * @Security("is_granted('edit', item)")
     *
     * @param LsItem $item
     * @param User $user
     *
     * @return JsonResponse
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
     * @Route("/cfitem/{id}/lock", name="lsitem_lock")
     * @Method({"POST"})
     * @Security("is_granted('edit', item)")
     *
     * @param LsItem $item
     * @param User $user
     *
     * @return JsonResponse
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
