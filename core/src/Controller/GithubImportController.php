<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ParseCsvGithubDocumentCommand;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Permission::FRAMEWORK_CREATE)]
class GithubImportController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/cf/github/import', name: 'import_from_github')]
    public function import(Request $request): JsonResponse
    {
        /** @var array $lsItemKeys - argument passed as an array */
        $lsItemKeys = $request->request->all('cfItemKeys');
        $fileContent = $request->request->get('content');
        $lsDocId = $request->request->get('lsDocId');
        $frameworkToAssociate = $request->request->get('frameworkToAssociate');
        $missingFieldsLog = $request->request->all('missingFieldsLog');

        $command = new ParseCsvGithubDocumentCommand($lsItemKeys, base64_decode($fileContent), $lsDocId, $frameworkToAssociate, $missingFieldsLog);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Success',
        ]);
    }
}
