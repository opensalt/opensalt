<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcher;
use App\Command\Framework\CopyFrameworkCommand;
use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
    use CommandDispatcher;
    /**
     * @Route("/framework/{id}")
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
        $em = $this->getDoctrine()->getManager();

        $type = $request->request->get('type');
        $frameworkToCopy = $request->request->get('frameworkToCopy');

        $toLsDoc = $em->getRepository(LsDoc::class)->find($frameworkToCopy);

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
