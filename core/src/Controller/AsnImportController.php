<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportAsnFromUrlCommand;
use App\Entity\User\User;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Permission::FRAMEWORK_CREATE)]
class AsnImportController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/cf/asn/import', name: 'import_from_asn')]
    public function importAsn(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $fileUrl = $request->request->get('fileUrl');
        $command = new ImportAsnFromUrlCommand($fileUrl, null, $user->getOrg());

        try {
            $this->sendCommand($command);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => 'Framework imported successfully!',
        ]);
    }
}
