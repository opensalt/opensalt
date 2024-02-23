<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CopyFrameworkCommand;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/copy')]
class CopyController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    #[Route(path: '/framework/{id}', name: 'copy_framework_content', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'lsDoc')]
    public function framework(Request $request, LsDoc $lsDoc): JsonResponse
    {
        $type = $request->request->get('type');
        $frameworkToCopy = $request->request->get('copyToFramework');
        $toLsDoc = $this->managerRegistry->getRepository(LsDoc::class)->find($frameworkToCopy);

        if (null === $toLsDoc) {
            $this->createNotFoundException('The target framework is not found.');
        }
        $this->denyAccessUnlessGranted(Permission::FRAMEWORK_EDIT, $toLsDoc, 'You do not have edit rights to the destination framework.');

        $command = new CopyFrameworkCommand($lsDoc, $toLsDoc, $type);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Framework successful copied!',
            'docDestinationId' => $frameworkToCopy,
            'frameworkToCopy' => $toLsDoc->getTitle(),
            'type' => $type,
        ]);
    }
}
