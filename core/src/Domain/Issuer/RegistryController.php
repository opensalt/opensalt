<?php

namespace App\Domain\Issuer;

use App\DTO\ItemType\OrganizationDto;
use App\Repository\Framework\IdentifierItemRepository;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/registry/issuer')]
class RegistryController extends AbstractController
{
    public function __construct(
        private readonly IdentifierItemRepository $identifierItemRepository,
    ) {
    }

    #[Route('/', name: 'issuer_registry_index', methods: ['GET'])]
    #[IsGranted(Permission::ISSUER_REGISTRY_LIST)]
    public function list(): Response
    {
        // Get list of organisations with identifiers
        $issuers = $this->identifierItemRepository->findIssuerItems();

        if (!$issuers) {
            throw $this->createNotFoundException('No issuers found');
        }

        /** @var array<OrganizationDto> $issuerDtos */
        $issuerDtos = [];
        foreach ($issuers as $issuer) {
            $issuerDtos[] = OrganizationDto::fromItem($issuer);
        }

        return $this->render('issuer_registry/list.html.twig', [
            'issuers' => $issuerDtos,
        ]);
    }

    #[Route('/{id}', name: 'issuer_registry_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $issuerInfo = $this->identifierItemRepository->findIssuerById($id);

        if (!$issuerInfo) {
            throw $this->createNotFoundException('Issuer not found');
        }

        /*
        if (!$this->isGranted(Permission::ISSUER_REGISTRY_VIEW, $issuer)) {
            throw $this->createAccessDeniedException('Access denied');
        }
        */

        $issuerOrg = OrganizationDto::fromItem($issuerInfo['issuer']);

        return $this->render('issuer_registry/show.html.twig', [
            'issuer' => $issuerOrg,
            'identifiers' => $issuerInfo['identifiers'],
        ]);
    }
}
