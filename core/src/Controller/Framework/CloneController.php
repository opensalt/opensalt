<?php

declare(strict_types=1);

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CloneFrameworkCommand;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CloneController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/clone/framework/{id}', name: 'clone_framework', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[Route(path: '/clone/framework/{identifier}', name: 'clone_framework_by_identifier', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'lsDoc')]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function framework(string $_route, LsDoc $lsDoc): Response
    {
        $command = new CloneFrameworkCommand($lsDoc);
        $this->sendCommand($command);
        $newLsDoc = $command->getNotificationEvent()->getDoc();

        if ('clone_framework_by_identifier' === $_route) {
            // TODO: This should use a route at some point
            return $this->redirect('/editor/' . $newLsDoc->getIdentifier());
        }

        return $this->redirectToRoute('doc_tree_view', ['slug' => $newLsDoc->getId(), 'edit' => 1]);
    }
}
