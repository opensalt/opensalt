<?php

namespace CftfBundle\Controller;

use CftfBundle\Form\Type\RemoteCftfServerType;
use CftfBundle\Form\Type\LsDocCreateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Form\Type\LsDocType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * LsDoc controller.
 *
 * @Route("/cfdoc")
 */
class LsDocController extends Controller
{
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

        $results = $em->getRepository('CftfBundle:LsDoc')->findBy(
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
            $user = $this->getUser();
            if ($lsDoc->getOwnedBy() === 'user') {
                $lsDoc->setUser($user);
            } else {
                $lsDoc->setOrg($user->getOrg());
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDoc);
            $em->flush();

            return $this->redirectToRoute(
                'doc_tree_view',
                array('slug' => $lsDoc->getSlug())
            );
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
     * Displays a form to edit an existing LsDoc entity.
     *
     * @Route("/{id}/edit", name="lsdoc_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('edit', lsDoc)")
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, LsDoc $lsDoc)
    {
        $ajax = $request->isXmlHttpRequest();

        $deleteForm = $this->createDeleteForm($lsDoc);
        $editForm = $this->createForm(
            LsDocType::class,
            $lsDoc,
            ['ajax' => $ajax]
        );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($lsDoc);
            $em->flush();

            if ($ajax) {
                return new Response('OK', Response::HTTP_ACCEPTED);
            }

            return $this->redirectToRoute(
                'lsdoc_edit',
                array('id' => $lsDoc->getId())
            );
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
            $this->getDoctrine()
                ->getManager()
                ->getRepository(LsDoc::class)
                ->deleteDocument($lsDoc);
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
            ->getRepository('CftfBundle:LsDoc')
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
