<?php

namespace App\Controller\Framework;

use App\Entity\Framework\AdditionalField;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use App\Repository\Framework\AdditionalFieldRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Type\AdditionalFieldType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * AdditionalField controller.
 *
 * @Route("/additionalfields")
 */
class AdditionalFieldController extends AbstractController
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var additionalFieldRepository
     */
    private $additionalFieldRepository;

    /**
     * @var formFactory
     */
    private $formFactory;

    /**
     * @var entityManager
     */
    private $entityManager;

    /**
     * @var router
     */
    private $router;

    public function __construct(\Twig_Environment $twig, AdditionalFieldRepository $additionalFieldRepository, FormFactoryInterface $formFactory, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->twig = $twig;
        $this->additionalFieldRepository = $additionalFieldRepository;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * List all AdditionalField entities.
     *
     * @Route("/", name="additionalfield_index")
     */
    public function index()
    {
      $html = $this->twig->render('framework/additional_field/index.html.twig', [
        'additional_fields' => $this->additionalFieldRepository->findAll()
      ]);
      return new Response($html);
    }

    /**
     * Create an AdditionalField entity.
     *
     * @Route("/new", name="additionalfield_new")
     */
    public function create(Request $request)
    {
      $additionalField = new AdditionalField();
      $form = $this->formFactory->create(AdditionalFieldType::class, $additionalField);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->persist($additionalField);
        $this->entityManager->flush();

        return new RedirectResponse($this->router->generate('additionalfield_index'));
      }

      return new Response(
        $this->twig->render('framework/additional_field/create.html.twig', ['form' => $form->createView()])
      );
    }

    /**
     * Show a AdditionalField entity.
     *
     * @Route("/{id}", name="additionalfield_show")
     */
    public function show(AdditionalField $additionalField)
    {
      $additionalField = $this->additionalFieldRepository->find($additionalField);

      return new Response(
        $this->twig->render('framework/additional_field/show.html.twig', ['additional_field' => $additionalField])
      );
    }

    /**
     * Update an AdditionalField entity.
     *
     * @Route("/edit/{id}", name="additionalfield_update")
     */
    public function update(AdditionalField $additionalField, Request $request)
    {
      $form = $this->formFactory->create(AdditionalFieldType::class, $additionalField);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->flush();

        return new RedirectResponse($this->router->generate('additionalfield_index'));
      }

      return new Response(
        $this->twig->render('framework/additional_field/create.html.twig', ['form' => $form->createView()])
      );
    }

    /**
     * Delete a AdditionalField entity.
     *
     * @Route("/delete/{id}", name="additionalfield_delete")
     */
    public function delete(AdditionalField $additionalField)
    {
      $this->entityManager->remove($additionalField);
      $this->entityManager->flush();

      return new RedirectResponse($this->router->generate('additional_index'));
    }

}
