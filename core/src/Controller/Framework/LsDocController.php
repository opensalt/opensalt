<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddDocumentCommand;
use App\Command\Framework\DeleteDocumentCommand;
use App\Command\Framework\DeriveDocumentCommand;
use App\Command\Framework\LockDocumentCommand;
use App\Command\Framework\UpdateDocumentCommand;
use App\Command\Framework\UpdateFrameworkCommand;
use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use App\Exception\AlreadyLockedException;
use App\Form\Type\LsDocCreateType;
use App\Form\Type\LsDocType;
use App\Form\Type\RemoteCaseServerType;
use Doctrine\Persistence\ManagerRegistry;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route(path: '/cfdoc')]
class LsDocController extends AbstractController
{
    use CommandDispatcherTrait;

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * Lists all LsDoc entities.
     */
    #[Route(path: '/', methods: ['GET'], name: 'lsdoc_index')]
    public function indexAction(?UserInterface $user = null): Response
    {
        $em = $this->managerRegistry->getManager();

        /** @var LsDoc[] $results */
        $results = $em->getRepository(LsDoc::class)->findForList();

        $lsDocs = [];
        $loggedIn = $user instanceof User;
        foreach ($results as $lsDoc) {
            // Optimization: All but "Private Draft" are viewable to everyone (if not mirrored), only auth check "Private Draft"
            if (($loggedIn && $this->isGranted('list', $lsDoc))
                || (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $lsDoc->getAdoptionStatus()
                    && null === $lsDoc->getMirroredFramework())) {
                $lsDocs[] = $lsDoc;
            }
        }

        return $this->render('framework/ls_doc/index.html.twig', ['lsDocs' => $lsDocs]);
    }

    /**
     * Show frameworks from a remote system.
     */
    #[Route(path: '/remote', methods: ['GET', 'POST'], name: 'lsdoc_remote_index')]
    public function remoteIndexAction(Request $request): Response
    {
        $form = $this->createForm(RemoteCaseServerType::class);
        $form->handleRequest($request);

        $docs = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $docs = $this->loadDocumentListFromHost($form->getData()['hostname']);
            } catch (\Exception $e) {
                $form->get('hostname')->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('framework/ls_doc/remote_index.html.twig', [
            'form' => $form->createView(),
            'docs' => $docs,
        ]);
    }

    protected function loadDocumentsFromServer(string $urlPrefix): ResponseInterface
    {
        $jsonClient = new Client();
        $list = $jsonClient->request(
            'GET',
            $urlPrefix.'/ims/case/v1p0/CFDocuments',
            [
                'timeout' => 60,
                'headers' => [
                    'Accept' => 'application/vnd.opensalt+json, application/json;q=0.8',
                ],
            ]
        );

        return $list;
    }

    /**
     * Creates a new LsDoc entity.
     *
     * @Security("is_granted('create', 'lsdoc')")
     */
    #[Route(path: '/new', methods: ['GET', 'POST'], name: 'lsdoc_new')]
    public function newAction(Request $request): Response
    {
        $lsDoc = new LsDoc();
        $form = $this->createForm(LsDocCreateType::class, $lsDoc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddDocumentCommand($lsDoc);
                $this->sendCommand($command);

                return $this->redirectToRoute(
                    'doc_tree_view',
                    ['slug' => $lsDoc->getSlug()]
                );
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new document: '.$e->getMessage()));
            }
        }

        return $this->render('framework/ls_doc/new.html.twig', [
            'lsDoc' => $lsDoc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a LsDoc entity.
     *
     * @Security("is_granted('view', lsDoc)")
     */
    #[Route(path: '/{id}.{_format}', methods: ['GET'], defaults: ['_format' => 'html'], name: 'lsdoc_show')]
    public function showAction(LsDoc $lsDoc, string $_format = 'html'): Response
    {
        if ('json' === $_format) {
            // Redirect?  Change Action for Template?
            return $this->render('framework/ls_doc/show.json.twig', [
                'lsDoc' => $lsDoc,
            ]);
        }

        $deleteForm = $this->createDeleteForm($lsDoc);

        return $this->render('framework/ls_doc/show.html.twig', [
            'lsDoc' => $lsDoc,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Update a framework given a CSV or external File.
     *
     * @Security("is_granted('edit', lsDoc)")
     *
     * @deprecated It appears this is unused now
     */
    #[Route(path: '/doc/{id}/update', methods: ['POST'], name: 'lsdoc_update')]
    public function updateAction(Request $request, LsDoc $lsDoc): Response
    {
        $response = new JsonResponse();
        $fileContent = $request->request->get('content');
        /** @var array $cfItemKeys - cfItemKeys is an array argument */
        $cfItemKeys = $request->request->get('cfItemKeys');
        $frameworkToAssociate = $request->request->get('frameworkToAssociate');

        $command = new UpdateFrameworkCommand($lsDoc, base64_decode($fileContent), $frameworkToAssociate, $cfItemKeys);
        $this->sendCommand($command);

        return $response->setData([
            'message' => 'Success',
        ]);
    }

    /**
     * Update a framework given a CSV or external File on a derivative framework.
     *
     * @Security("is_granted('create', 'lsdoc')")
     */
    #[Route(path: '/doc/{id}/derive', methods: ['POST'], name: 'lsdoc_update_derive')]
    public function deriveAction(Request $request, LsDoc $lsDoc): Response
    {
        $fileContent = $request->request->get('content');
        $frameworkToAssociate = $request->request->get('frameworkToAssociate');

        $command = new DeriveDocumentCommand($lsDoc, base64_decode($fileContent), $frameworkToAssociate);
        $this->sendCommand($command);
        $derivedDoc = $command->getDerivedDoc();

        return new JsonResponse([
            'message' => 'Success',
            'new_doc_id' => $derivedDoc->getId(),
        ]);
    }

    /**
     * Displays a form to edit an existing LsDoc entity.
     *
     * @Security("is_granted('edit', lsDoc)")
     */
    #[Route(path: '/{id}/edit', methods: ['GET', 'POST'], name: 'lsdoc_edit')]
    public function editAction(Request $request, LsDoc $lsDoc, UserInterface $user): Response
    {
        $ajax = $request->isXmlHttpRequest();

        try {
            $command = new LockDocumentCommand($lsDoc, $user);
            $this->sendCommand($command);
        } catch (AlreadyLockedException $e) {
            return $this->render(
                'framework/ls_doc/locked.html.twig',
                []
            );
        }

        $deleteForm = $this->createDeleteForm($lsDoc);
        $editForm = $this->createForm(
            LsDocType::class,
            $lsDoc,
            ['ajax' => $ajax]
        );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $command = new UpdateDocumentCommand($lsDoc);
                $this->sendCommand($command);

                if ($ajax) {
                    return new Response('OK', Response::HTTP_ACCEPTED);
                }

                return $this->redirectToRoute(
                    'lsdoc_edit',
                    ['id' => $lsDoc->getId()]
                );
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error upating new document: '.$e->getMessage()));
            }
        }

        $ret = [
            'lsDoc' => $lsDoc,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];

        if ($ajax && $editForm->isSubmitted() && !$editForm->isValid()) {
            return $this->render(
                'framework/ls_doc/edit.html.twig',
                $ret,
                new Response('', Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        return $this->render('framework/ls_doc/edit.html.twig', $ret);
    }

    /**
     * Deletes a LsDoc entity.
     *
     * @Security("is_granted('delete', lsDoc)")
     */
    #[Route(path: '/{id}', methods: ['DELETE'], name: 'lsdoc_delete')]
    public function deleteAction(Request $request, LsDoc $lsDoc): Response
    {
        if ($request->isXmlHttpRequest()) {
            $token = $request->request->get('token');
            if ($this->isCsrfTokenValid('DELETE '.$lsDoc->getId(), $token)) {
                try {
                    $this->deleteFramework($lsDoc);

                    return new JsonResponse('OK');
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => ['message' => 'Error deleting framework']], Response::HTTP_BAD_REQUEST);
                }
            }

            return new JsonResponse(['error' => ['message' => 'CSRF token invalid']], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createDeleteForm($lsDoc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->deleteFramework($lsDoc);
        }

        return $this->redirectToRoute('lsdoc_index');
    }

    /**
     * Finds and displays a LsDoc entity.
     *
     * @Security("is_granted('view', lsDoc)")
     */
    #[Route(path: '/{id}/export.{_format}', methods: ['GET'], requirements: ['_format' => '(json|html|null)'], defaults: ['_format' => 'json'], name: 'lsdoc_export')]
    public function exportAction(LsDoc $lsDoc, string $_format = 'json'): Response
    {
        if ('json' !== $_format) {
            $_format = 'html';
        }

        $items = $this->managerRegistry
            ->getRepository(LsDoc::class)
            ->findAllChildrenArray($lsDoc);

        return $this->render('framework/ls_doc/export.'.$_format.'.twig', [
            'lsDoc' => $lsDoc,
            'items' => $items,
        ]);
    }

    /**
     * Load the document list from a remote host.
     *
     * @throws \Exception
     */
    protected function loadDocumentListFromHost(string $hostname): ?array
    {
        // Remove any scheme or path from the passed value
        $hostname = preg_replace('#^(?:https?://)?([^/]+).*#', '$1', $hostname);

        try {
            $remoteResponse = $this->loadDocumentsFromServer(
                'https://'.$hostname
            );
        } catch (\Exception $e) {
            try {
                $remoteResponse = $this->loadDocumentsFromServer(
                    'http://'.$hostname
                );
            } catch (\Exception $e) {
                throw new \Exception("Could not access CASE API on {$hostname}.");
            }
        }

        try {
            $docJson = $remoteResponse->getBody()->getContents();
            $docs = json_decode($docJson, true, 512, JSON_THROW_ON_ERROR);
            $docs = $docs['CFDocuments'];
            foreach ($docs as $key => $doc) {
                if (empty($doc['creator'])) {
                    $docs[$key]['creator'] = 'Unknown';
                }
                if (empty($doc['title'])) {
                    $docs[$key]['title'] = 'Unknown';
                }
            }
            usort(
                $docs,
                function ($a, $b) {
                    if ($a['creator'] !== $b['creator']) {
                        return $a['creator'] <=> $b['creator'];
                    }

                    return $a['title'] <=> $b['title'];
                }
            );
        } catch (\Exception $e) {
            $docs = null;
        }

        return $docs;
    }

    protected function deleteFramework(LsDoc $lsDoc): void
    {
        $command = new DeleteDocumentCommand($lsDoc);
        $this->sendCommand($command);
    }

    /**
     * Creates a form to delete a LsDoc entity.
     */
    private function createDeleteForm(LsDoc $lsDoc): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction(
                $this->generateUrl(
                    'lsdoc_delete',
                    ['id' => $lsDoc->getId()]
                )
            )
            ->setMethod('DELETE')
            ->getForm();
    }
}
