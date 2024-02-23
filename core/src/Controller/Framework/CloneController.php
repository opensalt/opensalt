<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CloneFrameworkCommand;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/clone')]
class CloneController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/framework/{id}', name: 'clone_framework', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'lsDoc')]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function framework(LsDoc $lsDoc): Response
    {
        $command = new CloneFrameworkCommand($lsDoc);
        $this->sendCommand($command);
        $newLsDoc = $command->getNotificationEvent()->getDoc();

        return $this->redirectToRoute('doc_tree_view', ['slug' => $newLsDoc->getId(), 'edit' => 1]);
    }
}
