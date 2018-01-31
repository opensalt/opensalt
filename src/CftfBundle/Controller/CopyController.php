<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CopyFrameworkCommand;
use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Copy controller.
 *
 * @Route("/copy")
 */
class CopyController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * @Route("/framework/{id}", name="copy_framework_content")
     * @Method("POST")
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
