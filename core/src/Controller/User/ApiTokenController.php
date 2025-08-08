<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\ApiToken;
use App\Entity\User\User;
use App\Form\DTO\ApiTokenCreateDTO;
use App\Form\Type\ApiTokenCreateType;
use App\Repository\User\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account/tokens')]
#[IsGranted('ROLE_USER')]
class ApiTokenController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
    }

    #[Route(path: '/', name: 'account_token_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $tokens = $this->apiTokenRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $createDto = new ApiTokenCreateDTO();
        $createForm = $this->createForm(ApiTokenCreateType::class, $createDto, [
            'action' => $this->generateUrl('account_token_new'),
            'method' => Request::METHOD_POST,
        ]);

        $deleteForms = [];
        /** @var ApiToken $token */
        foreach ($tokens as $token) {
            $deleteForms[$token->id] = $this->createDeleteForm($token)->createView();
        }

        return $this->render('user/token/index.html.twig', [
            'tokens' => $tokens,
            'form' => $createForm->createView(),
            'delete_form' => $deleteForms,
        ]);
    }

    #[Route(path: '/new', name: 'account_token_new', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        $dto = new ApiTokenCreateDTO();
        $form = $this->createForm(ApiTokenCreateType::class, $dto);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            // Re-render index with errors
            $tokens = $this->apiTokenRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

            return $this->render('user/token/index.html.twig', [
                'tokens' => $tokens,
                'form' => $form->createView(),
            ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $expiresAt = null;
        if ($dto->expiresAt instanceof \DateTimeInterface) {
            // Normalize to date-only (midnight) and ensure immutable
            $expiresAt = \DateTimeImmutable::createFromFormat('Y-m-d', $dto->expiresAt->format('Y-m-d'))
                ?: \DateTimeImmutable::createFromInterface($dto->expiresAt);
        }

        $tokenEntity = ApiToken::createToken($user, (string) $dto->name, $expiresAt);

        $this->entityManager->persist($tokenEntity);
        $this->entityManager->flush();

        // Access the token header immediately after flush (id assigned); do not store it server-side.
        $tokenHeader = $tokenEntity->token; // e.g., t1-<encodedId>-<token>

        return $this->render('user/token/created.html.twig', [
            'token' => $tokenHeader,
            'name' => $dto->name,
            'expiresAt' => $expiresAt,
        ]);
    }

    #[Route(path: '/{id}', name: 'account_token_delete', requirements: ['id' => '\d+'], methods: ['DELETE', 'POST'])]
    public function delete(Request $request, #[CurrentUser] User $user, ApiToken $apiToken): RedirectResponse
    {
        // Only allow deleting your own token
        if ($apiToken->user->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createDeleteForm($apiToken);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($apiToken);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('account_token_index');
    }

    private function createDeleteForm(ApiToken $apiToken): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('account_token_delete', ['id' => $apiToken->id]))
            ->setMethod(Request::METHOD_DELETE)
            ->getForm();
    }
}
