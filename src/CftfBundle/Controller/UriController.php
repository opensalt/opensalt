<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class UriController extends Controller
{
    /**
     * Export an LSItem entity.
     *
     * @Route("/uri/{uri}", requirements={"uri"=".+"}, defaults={"_format"="html"}, name="editor_uri_lookup")
     * @Route("/uri/", defaults={"_format"="html"}, name="editor_uri_lookup_empty")
     * @Method("GET")
     * @Template()
     */
    public function findUriAction($uri = null)
    {
        $json = false;

        $em = $this->getDoctrine()->getManager();

        $localUri = $uri;
        if (Uuid::isValid($uri)) {
            // If the uri is just a UUID then assume it is a local one
            $localUri = 'local:'.$uri;
        }
        if (preg_match('/\.json$/', $uri)) {
            $json = true;
            $localUri = preg_replace('/\.json$/', '', $localUri);
        }
        $localPrefix = $this->generateUrl('editor_uri_lookup_empty', [], Router::ABSOLUTE_URL);
        if (0 === strpos($localUri, $localPrefix)) {
            $localUri = substr($localUri, strlen($localPrefix));
            $localUri = 'local:'.$localUri;
        }

        $item = $em->getRepository('CftfBundle:LsItem')
            ->findOneBy(['uri'=>$localUri]);
        if ($item) {
            if ($json) {
                return $this->forward('CftfBundle:Editor:viewItem', ['id' => $item->getId(), '_format' => 'json']);
            }
            //return $this->forward('CftfBundle:Editor:viewItem', ['id' => $item->getId(), '_format' => 'html']);
            return $this->redirectToRoute('editor_lsitem', ['id' => $item->getId()]);
        }

        $doc = $em->getRepository('CftfBundle:LsDoc')
            ->findOneBy(['uri'=>$localUri]);
        if ($doc) {
            if ($json) {
                return $this->forward('CftfBundle:Editor:viewDoc', ['id' => $doc->getId(), '_format' => 'json']);
            }
            //return $this->forward('CftfBundle:Editor:viewDoc', ['id' => $doc->getId(), '_format' => 'html']);
            return $this->redirectToRoute('editor_lsdoc', ['id' => $doc->getId()]);
        }

        $association = $em->getRepository('CftfBundle:LsAssociation')
            ->findOneBy(['uri'=>$localUri]);
        if ($association) {
            if ($json) {
                return $this->forward('CftfBundle:LsAssociation:export', ['id' => $association->getId(), '_format' => 'json']);
            }

            $hasOrigin = $association->getOrigin();

            if ($hasOrigin instanceof LsItem) {
                return $this->redirectToRoute('editor_lsitem', ['id' => $hasOrigin->getId()]);
            } elseif ($hasOrigin instanceof LsDoc) {
                return $this->redirectToRoute('editor_lsdoc', ['id' => $hasOrigin->getId()]);
            }

            // TODO: Show a view focused on the association
            return $this->redirectToRoute('lsassociation_show', ['id' => $association->getId()]);
        }

        return [
            'uri' => $uri,
        ];
    }
}
