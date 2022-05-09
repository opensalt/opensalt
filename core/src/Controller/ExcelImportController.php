<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportExcelFileCommand;
use App\Entity\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ExcelImportController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/salt/excel/import', name: 'import_excel_file', methods: ['POST'])]
    #[Security("is_granted('create', 'lsdoc')")]
    public function importExcelAction(Request $request, UserInterface $user): Response
    {
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }

        $file = $request->files->get('file');

        $command = new ImportExcelFileCommand($file->getRealPath(), null, $user->getOrg());
        $this->sendCommand($command);

        return new Response('OK', Response::HTTP_OK);
    }
}
