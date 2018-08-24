<?php

namespace App\Controller;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class UriController extends AbstractController
{
    /**
     * Export an LSItem entity.
     *
     * @Route("/uri/{uri}", methods={"GET"}, requirements={"uri"=".+"}, defaults={"_format"="html"}, name="editor_uri_lookup")
     * @Route("/uri/", methods={"GET"}, defaults={"_format"="html"}, name="editor_uri_lookup_empty")
     * @Template()
     *
     * @param Request $request
     * @param string $uri
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function findUriAction(Request $request, $uri = null)
    {
        $json = false;

        $localUri = $uri;
        $tryIdentifier = null;

        if (preg_match('/\.json$/', $uri)) {
            $json = true;
            $localUri = preg_replace('/\.json$/', '', $localUri);
        }

        // if this is an ajax call, assume the json version is wanted
        if ($request->isXmlHttpRequest()) {
            $json = true;
        }

        // PW: we need to do this check after the json check above
        if (Uuid::isValid($localUri)) {
            // If the uri is just a UUID then assume it is a local one
            $tryIdentifier = $localUri;
            $localUri = 'local:'.$localUri;
        }

        $localPrefix = $this->generateUrl('editor_uri_lookup_empty', [], Router::ABSOLUTE_URL);
        if (0 === strpos($localUri, $localPrefix)) {
            $localUri = substr($localUri, strlen($localPrefix));
            $localUri = 'local:'.$localUri;
        }

        if ($item = $this->findIfItem($json, $localUri, $tryIdentifier)) {
            return $item;
        }

        if ($doc = $this->findIfDoc($json, $localUri, $tryIdentifier)) {
            return $doc;
        }

        if ($association = $this->findIfAssociation($json, $localUri, $tryIdentifier)) {
            return $association;
        }

        return [
            'uri' => $uri,
        ];
    }

    /**
     * @param $json
     * @param $localUri
     * @param $tryIdentifier
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    private function findIfItem($json, $localUri, $tryIdentifier)
    {
        $item = $this->findByUriOrIdentifier(LsItem::class, $localUri, $tryIdentifier);
        if ($item) {
            if ($json) {
                return $this->forward('App\Controller\Framework\EditorController:viewItemAction', ['id' => $item->getId(), '_format' => 'json']);
            }
            //return $this->forward('App\Controller\Framework\EditorController:viewItemAction', ['id' => $item->getId(), '_format' => 'html']);
            return $this->redirectToRoute('doc_tree_item_view', ['id' => $item->getId()]);
        }

        return null;
    }

    /**
     * @param $json
     * @param $localUri
     * @param $tryIdentifier
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    private function findIfDoc($json, $localUri, $tryIdentifier)
    {
        $doc = $this->findByUriOrIdentifier(LsDoc::class, $localUri, $tryIdentifier);
        if ($doc) {
            if ($json) {
                //return $this->forward('App\Controller\Framework\EditorController:viewDocAction', ['id' => $doc->getId(), '_format' => 'json']);
                // http://127.0.0.1:3000/app_dev.php/uri/731cf3e4-43a2-4aa0-b2a7-87a49dac5374.json
                return $this->forward('App\Controller\Framework\CfPackageController:exportAction', ['id' => $doc->getId(), '_format' => 'json']);
            }
            //return $this->forward('App\Controller\Framework\EditorController:viewDocAction', ['id' => $doc->getId(), '_format' => 'html']);
            return $this->redirectToRoute('doc_tree_view', ['slug' => $doc->getSlug()]);
        }

        return null;
    }

    /**
     * @param $json
     * @param $localUri
     * @param $tryIdentifier
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    private function findIfAssociation($json, $localUri, $tryIdentifier)
    {
        $association = $this->findByUriOrIdentifier(LsAssociation::class, $localUri, $tryIdentifier);
        if ($association) {
            if ($json) {
                return $this->forward('App\Controller\Framework\LsAssociationController:exportAction', ['id' => $association->getId(), '_format' => 'json']);
            }

            $hasOrigin = $association->getOrigin();

            if ($hasOrigin instanceof LsItem) {
                return $this->redirectToRoute('doc_tree_item_view', ['id' => $hasOrigin->getId()]);
            }
            if ($hasOrigin instanceof LsDoc) {
                return $this->redirectToRoute('doc_tree_view', ['slug' => $hasOrigin->getSlug()]);
            }

            // TODO: Show a view focused on the association
            return $this->redirectToRoute('lsassociation_show', ['id' => $association->getId()]);
        }
    }

    /**
     * @param $class
     * @param $localUri
     * @param $tryIdentifier
     *
     * @return LsDoc|LsItem|LsAssociation|null
     */
    private function findByUriOrIdentifier($class, $localUri, $tryIdentifier) {
        $repository = $this->getDoctrine()->getManager()->getRepository($class);
        if(!empty($tryIdentifier)) {
            $entity = $repository->findOneBy(['identifier' => $tryIdentifier]);
            if(!empty($entity)) {
                return $entity;
            }
        }

        return $repository->findOneBy(['uri' => $localUri]);
    }
}
