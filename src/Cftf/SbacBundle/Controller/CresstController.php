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
    const ELA_PUBLICATION = 'TA-ELA-v1';
    const MATH_PUBLICATION = 'TA-Math-v1';

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
    public function exportAction(Request $request, LsDoc $lsDoc, $_format = 'html')
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
            if ('Target' !== $item->getType()) {
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
            self::ELA_PUBLICATION,
            'ELA',
            'English/Language Arts',
            '',
            'English/Language Arts',
            '',
        ];
        $csvLine = \Util\CsvUtil::arrayToCsv($line);
        $csvLines[] = $csvLine;

        // "Virtual" top level node for Math
        $line = [
            self::MATH_ROOT_UUID,
            '',
            self::MATH_PUBLICATION,
            'Math',
            'Math',
            '',
            'Math',
            '',
        ];
        $csvLine = \Util\CsvUtil::arrayToCsv($line);
        $csvLines[] = $csvLine;

        /** @var LsItem $item */
        foreach ($items as $item) {
            if (in_array($item->getType(), ['Domain', 'Conceptual Category'], true)) {
                // Merge Domain/Conceptual Category into Target
                continue;
            }

            $grade = $item->getEducationalAlignment();
            if (false !== strpos($grade, ',')) {
                $grade = 'HS';
            }

            // Expand the terms for the DL
            $parts = explode('.', $item->getHumanCodingScheme());
            if ('E' === $parts[0]) {
                $parts[0] = 'ELA';
                $publication = self::ELA_PUBLICATION;
            } else {
                $parts[0] = 'Math';
                $publication = self::MATH_PUBLICATION;
            }
            $parts[1] = str_replace('G', 'Grade', $parts[1]);
            if (!empty($parts[2])) {
                $parts[2] = preg_replace('/[A-Z]$/', '', $parts[2]);
                $parts[2] = str_replace('C', 'Claim', $parts[2]);
            }
            if (!empty($parts[3])) {
                $parts[3] = str_replace('T', 'Target', $parts[3]);
            }
            $code = implode('.', $parts);
            $item->setHumanCodingScheme($code);

            // Get parent
            $parentUuid = null;
            if ($parent = $item->getParentItem()) {
                $parentUuid = $parent->getIdentifier();
                $parentType = $parent->getType();

                if (in_array($parentType, ['Domain', 'Conceptual Category'], true)) {
                    // Merge the Target into the Domain/Conceptual Category
                    $parentUuid = $parent->getParentItem()->getIdentifier();

                    $item->setAbbreviatedStatement($parent->getAbbreviatedStatement().': '.$item->getAbbreviatedStatement());
                }
            } else {
                if (0 === strpos($code, 'E')) {
                    $parentUuid = self::ELA_ROOT_UUID;
                } else {
                    $parentUuid = self::MATH_ROOT_UUID;
                }
            }

            $crosswalk = $item->getExtraProperty('crosswalk', []);

            $line = [
                'uuid' => $item->getIdentifier(),
                'parentUuid' => $parentUuid,
                'publication' => $publication,
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
