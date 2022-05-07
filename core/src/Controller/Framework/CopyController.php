<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CopyFrameworkCommand;
use App\Entity\Framework\LsDoc;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Copy controller.
 */
#[Route(path: '/copy')]
class CopyController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @Security("is_granted('view', lsDoc)")
     */
    #[Route(path: '/framework/{id}', name: 'copy_framework_content', methods: ['POST'])]
    public function frameworkAction(Request $request, LsDoc $lsDoc): JsonResponse
    {
        $type = $request->request->get('type');
        $frameworkToCopy = $request->request->get('copyToFramework');
        $toLsDoc = $this->managerRegistry->getRepository(LsDoc::class)->find($frameworkToCopy);

        if (null === $toLsDoc) {
            $this->createNotFoundException('The target framework is not found.');
        }
        $this->denyAccessUnlessGranted('edit', $toLsDoc, 'You do not have edit rights to the destination framework.');

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
