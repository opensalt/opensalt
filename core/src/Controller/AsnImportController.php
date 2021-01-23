<?php

namespace App\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Import\ImportAsnFromUrlCommand;
use App\Entity\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AsnImportController
 *
 * @Security("is_granted('create', 'lsdoc')")
 */
class AsnImportController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * @Route("/cf/asn/import", name="import_from_asn")
     *
     * @return JsonResponse
     */
    public function importAsnAction(Request $request, UserInterface $user): Response
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ['message' => 'Invalid user']], Response::HTTP_UNAUTHORIZED);
        }

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
