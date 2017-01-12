<?php

namespace Cftf\SbacBundle\Controller;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CresstController extends Controller
{
    /**
     * @Route("/sbac/dl/export/{id}.{_format}", name="dl_export", defaults={"_format"="html"}, requirements={"id"="\d+"})
     * @Template()
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     * @param string $_format
     *
     * @return array
     */
    public function exportAction(Request $request, LsDoc $lsDoc, $_format = "html")
    {
        // Find all targets for the document
        // Go through each target
        //   find parent(s), merging to two above (Grade + Domain/...)
        //   find related skills and get the crosswalk information
        // Export as CSV
        // UUID, Parent UUID, Grade, Name, Short Name, publication, alignment key, Full Description, crosswalk

        $em = $this->getDoctrine()->getManager();
        $items = $em->getRepository('CftfBundle:LsItem')->findAllForDocWithAssociations($lsDoc);
        $items = new ArrayCollection($items);
        $items = $items->map(function (LsItem $item) {
            if ($item->getType() !== 'Target') {
                return $item;
            }

            $crosswalk = [];
            $related = $item->getInverseAssociations()->filter(function (LsAssociation $association) use (&$crosswalk) {
                if ($association->getType() !== LsAssociation::RELATED_TO) {
                    return false;
                }

                $origin = $association->getOriginLsItem();
                if ($origin->getType() === 'Measured Skill'){
                    $prop = $origin->getExtraProperty('source');
                    $crosswalk[] = $prop['legacyCodingScheme'];

                    return true;
                }

                return false;
            });

            $item->setExtraProperty('crosswalk', $crosswalk);

            return $item;
        });
        $items = $items->filter(function (LsItem $item) {
            return $item->getType() !== 'Measured Skill';
        });

        $csvLines = [];
        // UUID, Parent UUID, Grade, Name, Short Name, publication, alignment key, Full Description, crosswalk
        $line = [
            'uuid',
            'parentUuid',
            'publication',
            'name',
            'shortName',
            'grade',
            'alignmentKey',
            'fullDescription',
            'crosswalk',
        ];
        $csvLine = \Util\CsvUtil::arrayToCsv($line);
        $csvLines[] = $csvLine;

        /** @var LsItem $item */
        foreach ($items as $item) {
            $grade = $item->getEducationalAlignment();
            if (false !== strpos($grade, ',')) {
                $grade = 'HS';
            }

            $parentUuid = null;
            if ($parent = $item->getParentItem()) {
                $parentUuid = $parent->getIdentifier();
            }

            $crosswalk = $item->getExtraProperty('crosswalk', []);

            $line = [
                'uuid' => $item->getIdentifier(),
                'parentUuid' => $parentUuid,
                'publication' => 'TA-ELA-v1',
                'name' => $item->getHumanCodingScheme(),
                'shortName' => $item->getAbbreviatedStatement(),
                'grade' => $grade,
                'alignmentKey' => '',
                'fullDescription' => $item->getFullStatement(),
                'crosswalk' => implode('~', $crosswalk),
            ];

            $csvLine = \Util\CsvUtil::arrayToCsv($line);
            $csvLines[] = $csvLine;
        }

        return [
            'csvLines' => $csvLines,
        ];
    }
}
