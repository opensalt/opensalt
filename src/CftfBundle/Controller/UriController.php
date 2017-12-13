<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class UriController extends Controller
{
    /**
     * Export an LSItem entity.
     *
     * @Route("/uri/{uri}", requirements={"uri"=".+"}, defaults={"_format"="html"}, name="editor_uri_lookup")
     * @Route("/uri/", defaults={"_format"="html"}, name="editor_uri_lookup_empty")
     * @Method("GET")
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
            $localUri = 'local:'.$localUri;
        }

        $localPrefix = $this->generateUrl('editor_uri_lookup_empty', [], Router::ABSOLUTE_URL);
        if (0 === strpos($localUri, $localPrefix)) {
            $localUri = substr($localUri, strlen($localPrefix));
            $localUri = 'local:'.$localUri;
        }

        if ($item = $this->findIfItem($json, $localUri)) {
            return $item;
        }

        if ($doc = $this->findIfDoc($json, $localUri)) {
            return $doc;
        }

        if ($association = $this->findIfAssociation($json, $localUri)) {
            return $association;
        }

        return [
            'uri' => $uri,
        ];
    }

    /**
     * @param $json
     * @param $localUri
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    private function findIfItem($json, $localUri)
    {
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(LsItem::class)->findOneBy(['uri'=>$localUri]);
        if ($item) {
            if ($json) {
                return $this->forward('CftfBundle:Editor:viewItem', ['id' => $item->getId(), '_format' => 'json']);
            }
            //return $this->forward('CftfBundle:Editor:viewItem', ['id' => $item->getId(), '_format' => 'html']);
            return $this->redirectToRoute('doc_tree_item_view', ['id' => $item->getId()]);
        }
    }

    /**
     * @param $json
     * @param $localUri
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    private function findIfDoc($json, $localUri)
    {
        $em = $this->getDoctrine()->getManager();
        $doc = $em->getRepository(LsDoc::class)->findOneBy(['uri'=>$localUri]);
        if ($doc) {
            if ($json) {
                //return $this->forward('CftfBundle:Editor:viewDoc', ['id' => $doc->getId(), '_format' => 'json']);
                // http://127.0.0.1:3000/app_dev.php/uri/731cf3e4-43a2-4aa0-b2a7-87a49dac5374.json
                return $this->forward('CftfBundle:CfPackage:export', ['id' => $doc->getId(), '_format' => 'json']);
            }
            //return $this->forward('CftfBundle:Editor:viewDoc', ['id' => $doc->getId(), '_format' => 'html']);
            return $this->redirectToRoute('doc_tree_view', ['slug' => $doc->getSlug()]);
        }
    }

    /**
     * @param $json
     * @param $localUri
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    private function findIfAssociation($json, $localUri)
    {
        $em = $this->getDoctrine()->getManager();
        $association = $em->getRepository(LsAssociation::class)->findOneBy(['uri'=>$localUri]);
        if ($association) {
            if ($json) {
                return $this->forward('CftfBundle:LsAssociation:export', ['id' => $association->getId(), '_format' => 'json']);
            }

            $hasOrigin = $association->getOrigin();

            if ($hasOrigin instanceof LsItem) {
                return $this->redirectToRoute('doc_tree_item_view', ['id' => $hasOrigin->getId()]);
            } elseif ($hasOrigin instanceof LsDoc) {
                return $this->redirectToRoute('doc_tree_view', ['slug' => $hasOrigin->getSlug()]);
            }

            // TODO: Show a view focused on the association
            return $this->redirectToRoute('lsassociation_show', ['id' => $association->getId()]);
        }
    }
}
