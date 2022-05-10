<?php

namespace App\Controller\Framework;

use App\Entity\Framework\AdditionalField;
use App\Form\Type\AdditionalFieldType;
use App\Repository\Framework\AdditionalFieldRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/additionalfield')]
#[IsGranted(Permission::ADDITIONAL_FIELDS_MANAGE)]
class AdditionalFieldController extends AbstractController
{
    public function __construct(
        private readonly AdditionalFieldRepository $additionalFieldRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * List all AdditionalField entities.
     */
    #[Route(path: '/', name: 'additional_field_index')]
    public function index(): Response
    {
        return $this->render('framework/additional_field/index.html.twig', [
            'additional_fields' => $this->additionalFieldRepository->findAll(),
        ]);
    }

    /**
     * Create an AdditionalField entity.
     */
    #[Route(path: '/new', name: 'additional_field_new')]
    public function create(Request $request): Response
    {
        $additionalField = new AdditionalField();
        $form = $this->createForm(AdditionalFieldType::class, $additionalField);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($additionalField);
            $this->entityManager->flush();

            return $this->redirectToRoute('additional_field_index');
        }

        return $this->render('framework/additional_field/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show a AdditionalField entity.
     */
    #[Route(path: '/{id}', name: 'additional_field_show')]
    public function show(AdditionalField $additionalField): Response
    {
        return $this->render('framework/additional_field/show.html.twig', [
            'additional_field' => $additionalField,
        ]);
    }

    /**
     * Update an AdditionalField entity.
     */
    #[Route(path: '/edit/{id}', name: 'additional_field_update')]
    public function update(AdditionalField $additionalField, Request $request): Response
    {
        $form = $this->createForm(AdditionalFieldType::class, $additionalField);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('additional_field_index');
        }

        return $this->render('framework/additional_field/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete a AdditionalField entity.
     */
    #[Route(path: '/delete/{id}', name: 'additional_field_delete')]
    public function delete(AdditionalField $additionalField): RedirectResponse
    {
        $this->entityManager->remove($additionalField);
        $this->entityManager->flush();

        return $this->redirectToRoute('additional_field_index');
    }
}
