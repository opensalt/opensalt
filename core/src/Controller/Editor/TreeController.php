<?php

declare(strict_types=1);

namespace App\Controller\Editor;

use App\Entity\Framework\LsDoc;
use App\Repository\Framework\LsDocRepository;
use App\Security\Permission;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/framework/editor')]
class TreeController extends AbstractController
{
    public function __construct(
        private readonly LsDocRepository $docRepository,
    ) {
    }

    #[Route(path: '/tree/{identifier}', name: 'editor_tree', methods: ['GET'])]
    #[IsGranted(Permission::FRAMEWORK_VIEW, 'lsDoc')]
    public function getTree(
        #[MapEntity(mapping: ['identifier' => 'identifier'])] LsDoc $lsDoc,
        #[MapQueryParameter] ?string $mode = null,
    ): Response {
        $lightweight = 'lightweight' === $mode;

        $result = $this->docRepository->findTreeForDocument($lsDoc, $lightweight);

        $document = [
            'identifier' => $lsDoc->getIdentifier(),
            'uri' => $lsDoc->getUri(),
            'title' => $lsDoc->getTitle(),
            'description' => $lsDoc->getDescription(),
            'creator' => $lsDoc->getCreator(),
            'lastChangeDateTime' => $lsDoc->getChangedAt()?->format('c'),
            'adoptionStatus' => $lsDoc->getAdoptionStatus(),
            'language' => $lsDoc->getLanguage(),
            'version' => $lsDoc->getVersion(),
            'org' => $lsDoc->getOrg()?->getId(),
            'orgName' => $lsDoc->getOrg()?->getName(),
        ];

        $response = [
            'document' => $document,
            'tree' => $result['tree'],
        ];

        if (!$lightweight) {
            $response['definitions'] = $result['definitions'];
            $response['permissions'] = [
                'canEdit' => $this->isGranted(Permission::FRAMEWORK_EDIT, $lsDoc),
                'isAdmin' => $this->isGranted('ROLE_ADMIN'),
            ];
        }

        $jsonResponse = new JsonResponse($response);

        if (!$lightweight && null !== $lsDoc->getChangedAt()) {
            $etag = md5($lsDoc->getChangedAt()->format('U.u').$lsDoc->getIdentifier());
            $jsonResponse->setEtag($etag);
            $jsonResponse->setLastModified($lsDoc->getChangedAt());
            $jsonResponse->setMaxAge(0);
            $jsonResponse->setSharedMaxAge(0);
        }

        return $jsonResponse;
    }
}
