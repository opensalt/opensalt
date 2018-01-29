<?php

namespace CftfBundle\Controller;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddDocumentCommand;
use App\Command\Framework\DeleteDocumentCommand;
use App\Command\Framework\DeriveDocumentCommand;
use App\Command\Framework\LockDocumentCommand;
use App\Command\Framework\UpdateDocumentCommand;
use App\Command\Framework\UpdateFrameworkCommand;
use App\Exception\AlreadyLockedException;
use CftfBundle\Form\Type\RemoteCftfServerType;
use CftfBundle\Form\Type\LsDocCreateType;
use Salt\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Form\Type\LsDocType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * LsDoc controller.
 *
 * @Route("/cfdoc")
 */
class LsDocController extends Controller
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsDoc entities.
     *
     * @Route("/", name="lsdoc_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $results = $em->getRepository(LsDoc::class)->findBy(
            [],
            ['creator' => 'ASC', 'title' => 'ASC', 'adoptionStatus' => 'ASC']
        );

        $lsDocs = [];
        $authChecker = $this->get('security.authorization_checker');
        foreach ($results as $lsDoc) {
            if ($authChecker->isGranted('view', $lsDoc)) {
                $lsDocs[] = $lsDoc;
            }
        }

        return [
            'lsDocs' => $lsDocs,
        ];
    }

    /**
     * Show frameworks from a remote system
     *
     * @Route("/remote", name="lsdoc_remote_index")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @return array
     */
    public function remoteIndexAction(Request $request)
    {
        $form = $this->createForm(RemoteCftfServerType::class);
        $form->handleRequest($request);

        $docs = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $docs = $this->loadDocumentListFromHost($form->getData()['hostname']);
            } catch (\Exception $e) {
                $form->get('hostname')->addError(new FormError($e->getMessage()));
            }
        }

        return [
            'form' => $form->createView(),
            'docs' => $docs,
        ];
    }

    /**
     * @param string $urlPrefix
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function loadDocumentsFromServer(string $urlPrefix)
    {
        $client = $this->get('csa_guzzle.client.json');

        $list = $client->request(
            'GET',
            $urlPrefix.'/ims/case/v1p0/CFDocuments',
            [
                'timeout' => 60,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return $list;
    }

    /**
     * Creates a new LsDoc entity.
     *
     * @Route("/new", name="lsdoc_new")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
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
                    array('slug' => $lsDoc->getSlug())
                );
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new document: '.$e->getMessage()));
            }
        }

        return [
            'lsDoc' => $lsDoc,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a LsDoc entity.
     *
     * @Route("/{id}.{_format}", defaults={"_format"="html"}, name="lsdoc_show")
     * @Method("GET")
     * @Template()
     * @Security("is_granted('view', lsDoc)")
     *
     * @param LsDoc $lsDoc
     * @param string $_format
     *
     * @return array
     */
    public function showAction(LsDoc $lsDoc, $_format = 'html')
    {
        if ('json' === $_format) {
            // Redirect?  Change Action for Template?
            return ['lsDoc' => $lsDoc];
        }

        $deleteForm = $this->createDeleteForm($lsDoc);

        return [
            'lsDoc' => $lsDoc,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Update a framework given a CSV or external File.
     *
     * @Route("/doc/{id}/update", name="lsdoc_update")
     * @Method("POST")
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     */
    public function updateAction(Request $request, LsDoc $lsDoc)
    {
        $response = new JsonResponse();
        $fileContent = $request->request->get('content');
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
     * @Route("/doc/{id}/derive", name="lsdoc_update_derive")
     * @Method("POST")
     * @Security("is_granted('create', 'lsdoc')")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     *
     * @return Response
     */
    public function deriveAction(Request $request, LsDoc $lsDoc): Response
    {
        $fileContent = $request->request->get('content');
        $frameworkToAssociate = $request->request->get('frameworkToAssociate');

        $command = new DeriveDocumentCommand($lsDoc, base64_decode($fileContent), $frameworkToAssociate);
        $this->sendCommand($command);
        $derivedDoc = $command->getDerivedDoc();

        return new JsonResponse([
            'message' => 'Success',
            'new_doc_id' => $derivedDoc->getId()
        ]);
    }

    /**
     * Displays a form to edit an existing LsDoc entity.
     *
     * @Route("/{id}/edit", name="lsdoc_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     * @param User $user
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, LsDoc $lsDoc, UserInterface $user)
    {
        $ajax = $request->isXmlHttpRequest();

        try {
            $command = new LockDocumentCommand($lsDoc, $user);
            $this->sendCommand($command);
        } catch (AlreadyLockedException $e) {
            return $this->render(
                'CftfBundle:LsDoc:locked.html.twig',
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
                    array('id' => $lsDoc->getId())
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
                'CftfBundle:LsDoc:edit.html.twig',
                $ret,
                new Response('', Response::HTTP_UNPROCESSABLE_ENTITY)
            );
        }

        return $ret;
    }

    /**
     * Deletes a LsDoc entity.
     *
     * @Route("/{id}", name="lsdoc_delete")
     * @Method("DELETE")
     * @Security("is_granted('delete', lsDoc)")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsDoc $lsDoc)
    {
        $form = $this->createDeleteForm($lsDoc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new DeleteDocumentCommand($lsDoc);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsdoc_index');
    }

    /**
     * Finds and displays a LsDoc entity.
     *
     * @Route("/{id}/export.{_format}", requirements={"_format"="(json|html|null)"}, defaults={"_format"="json"}, name="lsdoc_export")
     * @Method("GET")
     * @Template()
     * @Security("is_granted('view', lsDoc)")
     *
     * @param LsDoc $lsDoc
     * @param string $_format
     *
     * @return array
     */
    public function exportAction(LsDoc $lsDoc, $_format = 'json')
    {
        $items = $this->getDoctrine()
            ->getRepository(LsDoc::class)
            ->findAllChildrenArray($lsDoc);

        return [
            'lsDoc' => $lsDoc,
            'items' => $items,
        ];
    }

    /**
     * Load the document list from a remote host
     *
     * @param string $hostname
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function loadDocumentListFromHost(string $hostname): array
    {
        // Remove any scheme or path from the passed value
        $hostname = preg_replace('#^(?:https?://)?([^/]+)(?:/.*)#', '$1', $hostname);

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
            $docs = json_decode($docJson, true);
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

    /**
     * Creates a form to delete a LsDoc entity.
     *
     * @param LsDoc $lsDoc The LsDoc entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LsDoc $lsDoc)
    {
        return $this->createFormBuilder()
            ->setAction(
                $this->generateUrl(
                    'lsdoc_delete',
                    array('id' => $lsDoc->getId())
                )
            )
            ->setMethod('DELETE')
            ->getForm();
    }
}
