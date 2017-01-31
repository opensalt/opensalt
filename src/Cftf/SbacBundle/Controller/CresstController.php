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
use Symfony\Component\HttpFoundation\Response;

class CresstController extends Controller
{
    const ELA_ROOT_UUID = '9100ef5e-d793-4184-bb5b-3686d0258549';
    const MATH_ROOT_UUID = 'c44427b4-8e4e-540f-a68b-dcdb1b7ff78d';

    /**
     * @Route("/sbac/dl/export/{id}.{_format}", name="dl_export", defaults={"_format"="html"}, requirements={"id"="\d+", "_format"="(html|csv)"})
     * @Template()
     *
     * @param Request $request
     * @param LsDoc $lsDoc
     * @param string $_format
     *
     * @return Response|array
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
            $item->getInverseAssociations()->filter(function (LsAssociation $association) use (&$crosswalk) {
                if (LsAssociation::RELATED_TO !== $association->getType()) {
                    return false;
                }

                $origin = $association->getOriginLsItem();
                if ('Measured Skill' === $origin->getType()) {
                    $prop = $origin->getExtraProperty('source');
                    if (!empty($prop['legacyCodingScheme'])) {
                        $crosswalk[] = $prop['legacyCodingScheme'];
                    }

                    return true;
                }

                return false;
            });

            $item->setExtraProperty('crosswalk', $crosswalk);

            return $item;
        });
        /** @var ArrayCollection $items */
        $items = $items->filter(function (LsItem $item) {
            return 'Measured Skill' !== $item->getType();
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
            'fullDescription',
            'crosswalk',
        ];
        $csvLine = \Util\CsvUtil::arrayToCsv($line);
        $csvLines[] = $csvLine;

        // "Virtual" top level node for ELA
        $line = [
            self::ELA_ROOT_UUID,
            '',
            'TA-ELA-v1',
            'E',
            'English/Language Arts',
            '',
            'English/Language Arts',
            '',
        ];
        $csvLine = \Util\CsvUtil::arrayToCsv($line);
        $csvLines[] = $csvLine;

        /** @var LsItem $item */
        foreach ($items as $item) {
            if ('Claim' === $item->getType()) {
                continue;
            }

            $grade = $item->getEducationalAlignment();
            if (false !== strpos($grade, ',')) {
                $grade = 'HS';
            }

            $parentUuid = null;
            if ($parent = $item->getParentItem()) {
                $parentUuid = $parent->getIdentifier();

                if ('Claim' === $parent->getType()) {
                    $parentUuid = $parent->getParentItem()->getIdentifier();

                    $item->setAbbreviatedStatement($parent->getAbbreviatedStatement().': '.$item->getAbbreviatedStatement());
                }
            } else {
                $parentUuid = self::ELA_ROOT_UUID;
            }

            $crosswalk = $item->getExtraProperty('crosswalk', []);

            $line = [
                'uuid' => $item->getIdentifier(),
                'parentUuid' => $parentUuid,
                'publication' => 'TA-ELA-v1',
                'name' => $item->getHumanCodingScheme(),
                'shortName' => $item->getAbbreviatedStatement(),
                'grade' => $grade,
                'fullDescription' => $item->getFullStatement(),
                'crosswalk' => implode('~', $crosswalk),
            ];

            $csvLine = \Util\CsvUtil::arrayToCsv($line);
            $csvLines[] = $csvLine;
        }

        if ('csv' === $_format) {
            $response = new Response(
                $this->renderView(
                    'CftfSbacBundle:Cresst:export.csv.twig',
                    [
                        'csvLines' => $csvLines,
                    ]
                ),
                200
            );
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename=cresst-ela.csv');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        return [
            'csvLines' => $csvLines,
        ];
    }
}
