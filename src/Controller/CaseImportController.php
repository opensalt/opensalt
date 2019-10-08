<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportCaseJsonCommand;
use App\Entity\User\User;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class CaseImportController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/salt/case/import", name="import_case_file")
     * @Security("is_granted('create', 'lsdoc')")
     */
    public function importAction(Request $request, UserInterface $user): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

        $content = base64_decode($request->request->get('fileContent'));

        $command = new ImportCaseJsonCommand($content, $user->getOrg(), $user);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Success',
        ]);
    }
}
