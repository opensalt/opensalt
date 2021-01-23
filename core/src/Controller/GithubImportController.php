<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ParseCsvGithubDocumentCommand;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GithubImportController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class GithubImportController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/cf/github/import", name="import_from_github")
     */
    public function importAction(Request $request): JsonResponse
    {
        $lsItemKeys = $request->request->get('cfItemKeys');
        $fileContent = $request->request->get('content');
        $lsDocId = $request->request->get('lsDocId');
        $frameworkToAssociate = $request->request->get('frameworkToAssociate');
        $missingFieldsLog = $request->request->get('missingFieldsLog', []);

        $command = new ParseCsvGithubDocumentCommand($lsItemKeys, base64_decode($fileContent), $lsDocId, $frameworkToAssociate, $missingFieldsLog);
        $this->sendCommand($command);

        return new JsonResponse([
            'message' => 'Success',
        ]);
    }
}
