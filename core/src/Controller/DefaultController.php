<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\FrontMatter\Entity\FrontMatterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly FrontMatterRepository $repository,
    ) {
    }

    #[Route(path: '/', name: 'salt_index')]
    public function index(): RedirectResponse
    {
        if (0 !== $this->repository->count(['filename' => 'front:index.html.twig'])) {
            return $this->redirectToRoute('front_matter', ['path' => 'index']);
        }

        return $this->redirectToRoute('lsdoc_index');
    }
}
