<?php

namespace App\Controller\Framework;

use App\Entity\Framework\TaskModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use App\Repository\Framework\TaskModelRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Type\TaskModelType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * TaskModel controller.
 *
 * @Route("/taskmodels")
 */
class TaskModelController extends AbstractController
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var taskModelRepository
     */
    private $taskModelRepository;

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

    public function __construct(\Twig_Environment $twig, TaskModelRepository $taskModelRepository, FormFactoryInterface $formFactory, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->twig = $twig;
        $this->taskModelRepository = $taskModelRepository;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * List all TaskModel entities.
     *
     * @Route("/", name="taskmodel_index")
     */
    public function index()
    {
      $html = $this->twig->render('framework/task_model/index.html.twig', [
        'task_models' => $this->taskModelRepository->findAll()
      ]);
      return new Response($html);
    }

    /**
     * Create a TaskModel entity.
     *
     * @Route("/new", name="taskmodel_new")
     */
    public function create(Request $request)
    {
      $taskModel = new TaskModel();
      $form = $this->formFactory->create(TaskModelType::class, $taskModel);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->persist($taskModel);
        $this->entityManager->flush();

        return new RedirectResponse($this->router->generate('taskmodel_index'));
      }

      return new Response(
        $this->twig->render('framework/task_model/create.html.twig', ['form' => $form->createView()])
      );
    }

    /**
     * Show a TaskModel entity.
     *
     * @Route("/{id}", name="taskmodel_show")
     */
    public function show(TaskModel $taskModel)
    {
      $taskModel = $this->taskModelRepository->find($taskModel);

      return new Response(
        $this->twig->render('framework/task_model/show.html.twig', ['task_model' => $taskModel])
      );
    }

    /**
     * Update a TaskModel entity.
     *
     * @Route("/edit/{id}", name="taskmodel_update")
     */
    public function update(TaskModel $taskModel, Request $request)
    {
      $form = $this->formFactory->create(TaskModelType::class, $taskModel);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->flush();

        return new RedirectResponse($this->router->generate('taskmodel_index'));
      }

      return new Response(
        $this->twig->render('framework/task_model/create.html.twig', ['form' => $form->createView()])
      );
    }

    /**
     * Delete a TaskModel entity.
     *
     * @Route("/delete/{id}", name="taskmodel_delete")
     */
    public function delete(TaskModel $taskModel)
    {
      $this->entityManager->remove($taskModel);
      $this->entityManager->flush();

      return new RedirectResponse($this->router->generate('taskmodel_index'));
    }

}
