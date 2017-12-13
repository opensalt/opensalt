<?php

namespace GithubFilesBundle\Controller;

use App\Command\CommandDispatcher;
use App\Command\Import\ParseCsvGithubDocumentCommand;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class ImportController extends Controller
{
    use CommandDispatcher;

    /**
     * @Route("/cf/github/import", name="import_from_github")
     *
     * @param Request $request
     *
     * @return JsonResponse
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
