<?php

namespace App\Controller;

use App\Command\CommandDispatcher;
use App\Command\Import\ImportCaseJsonCommand;
use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class CaseImportController extends Controller
{
    use CommandDispatcher;

    public function __construct(ContainerInterface $container = null)
    {
        // event_dispatcher
        $this->setContainer($container);
    }

    /**
     * @Route("/salt/case/import", name="import_case_file")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @param Request $request
     * @param UserInterface $user
     *
     * @return JsonResponse
     */
    public function importAction(Request $request, UserInterface $user): Response
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $content = base64_decode($request->request->get('fileContent'));
        $fileContent = json_decode($content);

        $command = new ImportCaseJsonCommand($fileContent, $user->getOrg());
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Success'
        ]);
    }
}
