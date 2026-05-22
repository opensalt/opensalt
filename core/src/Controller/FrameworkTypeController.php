<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Framework\FrameworkType;
use App\Repository\Framework\FrameworkTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/cfdef/framework_type')]
class FrameworkTypeController extends AbstractController
{
    public function __construct(
        private readonly FrameworkTypeRepository $frameworkTypeRepository,
    ) {
    }

    /**
     * Lists all FrameworkType entities as JSON.
     */
    #[Route(path: '/list', name: 'framework_type_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $frameworkTypes = $this->frameworkTypeRepository->getList();

        $data = array_map(function (FrameworkType $frameworkType) {
            return [
                'id' => $frameworkType->getId(),
                'frameworkType' => $frameworkType->getFrameworkType(),
            ];
        }, $frameworkTypes);

        return $this->json($data);
    }
}
