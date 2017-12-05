<?php

namespace App\Controller;

use App\Command\CommandInterface;
use App\Command\Framework\AddDocumentCommand;
use App\Event\CommandEvent;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Form\Type\LsDocCreateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

class FrameworkController extends Controller
{
    public function __construct(ContainerInterface $container = null)
    {
        // form.factory
        // event_dispatcher
        // twig
        $this->container = $container;
    }

    /**
     * @Route("/test/new")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newFrameworkAction(Request $request)
    {
        $lsDoc = new LsDoc();
        $form = $this->createForm(LsDocCreateType::class, $lsDoc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddDocumentCommand($lsDoc);
                $this->sendCommand($command);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new document: '. $e->getMessage()));
            }

            if (0 === count($form->getErrors())) {
                return $this->redirectToRoute(
                    'doc_tree_view',
                    array('slug' => $lsDoc->getSlug())
                );
            }
        }

        return $this->render('framework/new.html.twig', [
            'lsDoc' => $lsDoc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Send a command to be handled
     *
     * @param CommandInterface $command
     */
    protected function sendCommand(CommandInterface $command): void
    {
        $this->get('event_dispatcher')->dispatch(
            CommandEvent::class,
            new CommandEvent($command)
        );
    }
}
