<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CopyFrameworkCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Copy controller.
 *
 * @Route("/copy")
 */
class CopyController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/framework/{id}", name="copy_framework_content", methods={"POST"})
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     * @param LsDoc $toLsDoc
     *
     * @return array
     */
    public function frameworkAction(Request $request, LsDoc $lsDoc)
    {
        $eManager = $this->getDoctrine()->getManager();

        $type = $request->request->get('type');
        $frameworkToCopy = $request->request->get('frameworkToCopy');

        $toLsDoc = $eManager->getRepository(LsDoc::class)->find($frameworkToCopy);

        $command = new CopyFrameworkCommand($lsDoc, $toLsDoc, $type);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Framework successful copied!',
            'docDestinationId' => $frameworkToCopy,
            'frameworkToCopy' => $toLsDoc->getTitle(),
            'type' => $type
        ]);
    }

}
