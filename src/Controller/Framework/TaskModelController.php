<?php

namespace App\Controller\Framework;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * TaskModel controller.
 *
 * @Route("/taskmodels")
 */
class TaskModelController extends AbstractController
{

    /**
     * List all TaskModel entities.
     *
     * @Route("/", name="taskmodel_index")
     */
    public function index()
    {
      return new Response(
        '<html><body>index</body></html>'
      );
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
