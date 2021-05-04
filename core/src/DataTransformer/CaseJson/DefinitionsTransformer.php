<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFDefinition;
use App\DTO\CaseJson\Definitions;

class DefinitionsTransformer
{
    public function __construct(
        private AssociationGroupingsTransformer $associationGroupingsTransformer,
        private ConceptsTransformer $conceptsTransformer,
        private ItemTypesTransformer $itemTypesTransformer,
        private LicencesTransformer $licencesTransformer,
        private SubjectsTransformer $subjectsTransformer,
    ) {
    }

    public function transform(?CFDefinition $definitions): Definitions
    {
        $defObjs = new Definitions();

        if (null === $definitions) {
            return $defObjs;
        }

        $defObjs->associationGroupings = $this->associationGroupingsTransformer->transform($definitions->cfAssociationGroupings);
        $defObjs->concepts = $this->conceptsTransformer->transform($definitions->cfConcepts);
        $defObjs->itemTypes = $this->itemTypesTransformer->transform($definitions->cfItemTypes);
        $defObjs->licences = $this->licencesTransformer->transform($definitions->cfLicenses);
        $defObjs->subjects = $this->subjectsTransformer->transform($definitions->cfSubjects);

        return $defObjs;
    }
}
