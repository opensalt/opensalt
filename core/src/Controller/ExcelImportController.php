<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportExcelFileCommand;
use App\Entity\User\User;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ExcelImportController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/salt/excel/import', name: 'import_excel_file', methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function importExcel(Request $request, #[CurrentUser] User $user): Response
    {
        $file = $request->files->get('file');

        $command = new ImportExcelFileCommand($file->getRealPath(), null, $user->getOrg());
        $this->sendCommand($command);

        return new Response('OK', Response::HTTP_OK);
    }
}
