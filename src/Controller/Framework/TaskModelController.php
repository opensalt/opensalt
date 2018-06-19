<?php

namespace App\Controller\Framework;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Framework\TaskModelRepository;

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

    public function __construct(\Twig_Environment $twig, taskModelRepository $taskModelRepository)
    {
        $this->twig = $twig;
        $this->taskModelRepository = $taskModelRepository;
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
    public function create()
    {
      return new Response(
        '<html><body>create</body></html>'
      );
    }

    /**
     * Show a TaskModel entity.
     *
     * @Route("/{id}", name="taskmodel_show")
     */
    public function show($id)
    {
      return new Response(
        '<html><body>show</body></html>'
      );
    }

    /**
     * Update a TaskModel entity.
     *
     * @Route("/{id}", name="taskmodel_update")
     */
    public function update($id)
    {
      return new Response(
        '<html><body>update</body></html>'
      );
    }

    /**
     * Delete a TaskModel entity.
     *
     * @Route("/{id}", name="taskmodel_delete")
     */
    public function delete($id)
    {
      return new Response(
        '<html><body>delete</body></html>'
      );
    }

}
