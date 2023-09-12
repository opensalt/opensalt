<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Framework\IdentifiableInterface;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubtypeUpdater
{
    private const MAP_TO_ASSOC_TYPES = [
        'exactMatchOf' => LsAssociation::EXACT_MATCH_OF,
        'isRelatedTo' => LsAssociation::RELATED_TO,
        'isPartOf->' => LsAssociation::PART_OF,
        '<-isPartOf' => LsAssociation::PART_OF,
    ];

    private const MAP_TYPES = [
        'exactMatchOf' => [
            'Identical',
            'Equivalent',
            'Examples Differ',
            'Identifiers Differ',
//            'Identifier Differ', /* Correct to Identifiers Differ */
        ],
        'isRelatedTo' => [
            'Major Alignment',
            'Mapped by State',
            'Mapped by Third Party',
            'Mapped',
            'Conceptual Alignment',
            'Similar To/Draws From',
        ],
        'isPartOf->' => [
            'Split',
            'Split/Major Alignment',
        ],
        '<-isPartOf' => [
            'Consolidated',
            'Consolidated/Major Alignment',
//            'Component',
        ],
        '' => [
//            'Addition',
//            'Not included',
//            'Not Included',
//            'Structural Element - Content',
//            'Structual Element - New',
            'Structural Element - New',
            'Reinforcement',
//            'Unmapped',
//            'Empty',
//            'Continuation',
        ],
    ];

    private const MAP_SUBTYPES = [
        'Identical' => 'exactMatchOf',
        'Equivalent' => 'exactMatchOf',
        'Examples Differ' => 'exactMatchOf',
        'Identifiers Differ' => 'exactMatchOf',
        'Identifier Differ' => 'exactMatchOf', /* Correct this */
        'Major Alignment' => 'isRelatedTo',
        'Mapped by State' => 'isRelatedTo',
        'Mapped by Third Party' => 'isRelatedTo',
        'Mapped' => 'isRelatedTo',
        'Conceptual Alignment' => 'isRelatedTo',
        'Similar To/Draws From' => 'isRelatedTo',
        'Split' => 'isPartOf->',
        'Split/Major Alignment' => 'isPartOf->',
        'Consolidated' => '<-isPartOf',
        'Consolidated/Major Alignment' => '<-isPartOf',
//        'Component' => '<-isPartOf',
        'Structural Element - New' => '',
        'Reinforcement' => '',
//        'Addition' => '',
//        'Not included' => '',
//        'Not Included' => '',
//        'Structural Element - Content' => '',
//        'Structual Element - New' => '',
//        'Unmapped' => '',
//        'Empty' => '',
//        'Continuation' => '',
    ];

    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function loadSpreadsheet(string $path): array
    {
        /* Columns
         * 1 identifier
         * 2 smartLevel
         * 3 humanCodingScheme
         * 4 fullStatement
         * 5 AssociationType
         * 6 Matched humanCodingScheme
         * 7 Matched fullStatement
         * 8 Matched Identifier
         * 9 notes
         * 10 Diff
         * 11 New Association Type
         * 12 Association Sub-type
         * 13 Association Annotation
         */

        $lineOutput = [];

        $types = [];

        try {
            $phpExcelObject = IOFactory::load($path);
        } catch (\Exception) {
            $lineOutput[] = ['row' => '', 'msg' => 'Error Loading file'];

            return $lineOutput;
        }

        try {
            $sheet = $phpExcelObject->getSheet($phpExcelObject->getFirstSheetIndex());
        } catch (\Exception) {
            $lineOutput[] = ['row' => '', 'msg' => 'Error finding first sheet'];

            return $lineOutput;
        }
        $rows = $sheet->getHighestRow();

        $this->registry->getConnection()->beginTransaction();

        $iterator = $sheet->getRowIterator(2, $rows);
        foreach ($iterator as $row) {
            $rowIndex = $row->getRowIndex();

            $originIdentifier = $this->getValueFromSheet($sheet, 1, $rowIndex);
            $destinationIdentifier = $this->getValueFromSheet($sheet, 8, $rowIndex);
            //$oldType = $this->getValueFromSheet($sheet, 5, $rowIndex);

            $type = $this->getValueFromSheet($sheet, 11, $rowIndex);
            $subtype = $this->getValueFromSheet($sheet, 12, $rowIndex);
//            $type = trim($type);
//            $type = preg_replace('/[^A-Za-z]*/', '', $type);
//            $type = strtolower($type);
//            $type = preg_replace('/^<-ispartof$/', 'haspart', $type);
//            $type = preg_replace('/^ispartof->$/', 'ispartof', $type);
            $types[$type] ??= [];
            $types[$type][$subtype] = 1;

            $annotation = $this->getValueFromSheet($sheet, 13, $rowIndex);

            $lineOutput[] = ['row' => (string) $rowIndex, 'msg' => $this->replaceAssociation($originIdentifier, $destinationIdentifier, $type, $subtype, $annotation)];
        }

        $phpExcelObject->disconnectWorksheets();
        $phpExcelObject->garbageCollect();
        unset($phpExcelObject);

        $this->registry->getManager()->flush();
        $this->registry->getConnection()->commit();
        $this->registry->getManager()->clear();

        return $lineOutput;
    }

    protected function getValueFromSheet(Worksheet $sheet, int $columnIndex, int $rowIndex): ?string
    {
        if (!$sheet->cellExists([$columnIndex, $rowIndex])) {
            return null;
        }

        return $sheet->getCell([$columnIndex, $rowIndex])->getFormattedValue();
    }

    protected function replaceAssociation(?string $origin, ?string $destination, ?string $newType, ?string $subtype, ?string $annotation): string
    {
        $ret = '';

        if (empty($origin) || empty($destination)) {
            // Skip row if both identifiers are not there
//            $this->io->comment('Missing identifiers');
            return "Missing identifiers\n";
        }

        if ('Identifier Differ' === $newType) {
            // Fix to use a single value for the new type
            $newType = 'Identifiers Differ';
        }

//        if ('haspart' === preg_replace('/[^a-zA-Z]/', '', strtolower($newType))) {
//            $newType = '<-isPartOf';
//        }

        if (!array_key_exists($subtype, self::MAP_SUBTYPES)) {
            return 'Bad subtype: "'.$subtype.'" ('.$newType.')'."\n";
        }

        if (strtolower(self::MAP_SUBTYPES[$subtype]) !== strtolower($newType)) {
            return 'Subtype does not match: "'.$newType.' / '.$subtype.'"'."\n";
        }

        $assocFrameworks = [];
        $assocs = $this->findAssociations($origin, $destination, $assocFrameworks);
        $rAssocs = $this->findAssociations($destination, $origin, $assocFrameworks);

        /*
        if (count($assocFrameworks) > 1) {
            dump(['frameworks' => $assocFrameworks]);
        }

        if (count($assocs) + count($rAssocs) > 1) {
            dump(['multiple associations' => [count($assocs), count($rAssocs)]]);
        }
        */

        $newType = self::MAP_SUBTYPES[$subtype];

        if (empty($newType)) {
//            $this->io->comment('Missing new type');
            foreach ($assocs as $assoc) {
//                dump(['found one maybe removable?' => [$origin, $destination, $assoc->getType(), $newType, $subtype]]);
                $doc = $assoc->getLsDoc();
                if (null !== $doc->getMirroredFramework() && $doc->getMirroredFramework()->isInclude()) {
                    $ret .= "Left association with empty new type from Read-Only framework\n";
                    continue;
                }

                $this->registry->getManager()->remove($assoc);
                $ret .= "Removed association with empty new type\n";
            }

            return $ret;
        }

        if (count($assocs) + count($rAssocs) < 1) {
            //dump(['no existing association', $origin, $destination, $newType, $subtype]);
            $ret .= "No existing association to change (What framework would a new one be in?)\n";
        }

        foreach ($assocs as $assoc) {
            $reversed = '<-isPartOf' === $newType;
            $ret .= $this->updateAssociation($reversed, $newType, $subtype, $annotation, $assoc);
        }

        foreach ($rAssocs as $assoc) {
            $reversed = '<-isPartOf' !== $newType;
            $ret .= $this->updateAssociation($reversed, $newType, $subtype, $annotation, $assoc);
        }

        return $ret;
    }

    protected function findAssociations(?string $origin, ?string $destination, array &$assocFrameworks): array
    {
        $assocRepo = $this->registry->getRepository(LsAssociation::class);

        $assocs = $assocRepo->findBy([
            'originNodeIdentifier' => $origin,
            'destinationNodeIdentifier' => $destination,
            'type' => [LsAssociation::EXACT_MATCH_OF, LsAssociation::RELATED_TO, LsAssociation::PART_OF],
        ]);
        foreach ($assocs as $assoc) {
            $docId = $assoc->getLsDoc()->getId();
            $assocFrameworks[$docId] = ($assocFrameworks[$docId] ?? 0) + 1;
        }

        return $assocs;
    }

    protected function updateAssociation(bool $reversed, string $newType, string $subtype, string $annotation, LsAssociation $assoc): string
    {
        /** @var LsDoc $doc */
        $doc = $assoc->getLsDoc();
        if (null !== $doc->getMirroredFramework() && $doc->getMirroredFramework()->isInclude()) {
            // Do not update associations from a mirrored framework
            return "Association{Not changed as is in a Read-Only framework}\n";
        }

        $changed = [];

        if ($reversed) {
            // Swap origin and destination as we need to point the other direction
            $oldOrigin = $assoc->getOrigin();
            $oldOriginIdentifier = $assoc->getOriginNodeIdentifier();
            $oldOriginUri = $assoc->getOriginNodeUri();

            $oldDestination = $assoc->getDestination();
            $oldDestinationIdentifier = $assoc->getDestinationNodeIdentifier();
            $oldDestinationUri = $assoc->getDestinationNodeUri();

            if ($oldOrigin instanceof IdentifiableInterface) {
                $assoc->setDestination($oldOrigin);
            } else {
                $assoc->setDestination($oldOriginUri, $oldOriginIdentifier);
            }

            if ($oldDestination instanceof IdentifiableInterface) {
                $assoc->setOrigin($oldDestination);
            } else {
                $assoc->setOrigin($oldDestinationUri, $oldDestinationIdentifier);
            }

            $changed[] = 'Reversed';
        }

        $newAssocType = self::MAP_TO_ASSOC_TYPES[$newType];

        $oldType = $assoc->getType();
        if ($oldType !== $newAssocType) {
            $assoc->setType($newAssocType);
            $changed[] = 'Type set';
        }

        $oldSubType = $assoc->getSubtype();
        if ($oldSubType !== $subtype) {
            $assoc->setSubtype($subtype);
            $changed[] = 'Subtype set';
        }

        $oldAnnotation = $assoc->getAnnotation();
        if ($oldAnnotation !== $annotation) {
            $assoc->setAnnotation($annotation);
            $changed[] = 'Annotation set';
        }

        if (0 === count($changed)) {
            return "Association{Unchanged}\n";
        }

        return 'Association{'.implode(',', $changed)."}\n";
    }
}
