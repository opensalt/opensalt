<?php

namespace CftfBundle\Controller;

use CftfBundle\Entity\LsDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Editor Tree controller.
 *
 * @Route("/cftree")
 */
class DocTreeController extends Controller
{
    /**
     * @Route("/lsdoc/{id}.{_format}", name="doc_tree_view", defaults={"_format"="html"}, requirements={"id"="\d+"})
     * @Method({"GET"})
     * @Template()
     */
    public function viewAction(LsDoc $lsDoc1, $_format = 'html')
    {
        return [
            'lsDoc1' => $lsDoc1,
        ];
    }

    /**
     * @Route("/lsdoc/{lsDoc1_id}/{lsDoc2_id}.{_format}", name="doc_tree_view2", defaults={"_format"="html"}, requirements={"lsDoc1_id"="\d+", "lsDoc2_id"="\d+"})
     * @ParamConverter("lsDoc1", class="CftfBundle:LsDoc", options={"id"="lsDoc1_id"})
     * @ParamConverter("lsDoc2", class="CftfBundle:LsDoc", options={"id"="lsDoc2_id"})
     * @Method({"GET"})
     * @Template()
     *
     * @param \CftfBundle\Entity\LsDoc $lsDoc1
     * @param \CftfBundle\Entity\LsDoc $lsDoc2
     *
     * @return array
     */
    public function view2Action(LsDoc $lsDoc1, LsDoc $lsDoc2, $_format = 'html')
    {
        return [
            'lsDoc1' => $lsDoc1,
            'lsDoc2' => $lsDoc2,
        ];
    }
}
