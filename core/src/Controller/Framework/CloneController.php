<?php

declare(strict_types=1);

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\CloneFrameworkCommand;
use App\Entity\Framework\LsDoc;
use App\Security\Permission;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CloneController extends AbstractController
{
    use CommandDispatcherTrait;

    #[Route(path: '/clone/framework/{id}', name: 'clone_framework', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[Route(path: '/clone/framework/{identifier}', name: 'clone_framework_by_identifier', requirements: ['identifier' => '[a-fA-F0-9]{8}-([a-fA-F0-9]{4}-){3}[a-fA-F0-9]{12}'], methods: ['POST'])]
    #[IsGranted(Permission::FRAMEWORK_EDIT, 'lsDoc')]
    #[IsGranted(Permission::FRAMEWORK_CREATE)]
    public function framework(
        Request $request,
        string $_route,
        #[MapEntity(expr: '((id ?? null) == null) ? repository.findOneByIdentifier(identifier ?? null) : repository.find(id ?? null)')] LsDoc $lsDoc,
    ): Response {
        $payload = $request->getPayload();
        $associateWithOriginal = filter_var($payload->get('associateWithOriginal', false), FILTER_VALIDATE_BOOL);
        $copyAssociations = filter_var($payload->get('copyAssociations', false), FILTER_VALIDATE_BOOL);

        $command = new CloneFrameworkCommand($lsDoc, $associateWithOriginal, $copyAssociations);
        $this->sendCommand($command);
        $newLsDoc = $command->getNotificationEvent()->getDoc();

        if ('json' === $request->getPreferredFormat('html')) {
            $redirectUrl = 'clone_framework_by_identifier' === $_route
                ? '/editor/'.$newLsDoc->getIdentifier()
                : $this->generateUrl('editor_shell_path', ['path' => $newLsDoc->getIdentifier()]);

            return new JsonResponse([
                'identifier' => $newLsDoc->getIdentifier(),
                'redirectUrl' => $redirectUrl,
            ], Response::HTTP_CREATED);
        }

        if ('clone_framework_by_identifier' === $_route) {
            // TODO: This should use a route at some point
            return $this->redirect('/editor/'.$newLsDoc->getIdentifier());
        }

        return $this->redirectToRoute('editor_shell_path', ['path' => $newLsDoc->getIdentifier()]);
    }
}
