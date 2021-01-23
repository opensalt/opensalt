<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFDefinition;
use App\DTO\CaseJson\Definitions;

class DefinitionsTransformer
{
    /**
     * @var AssociationGroupingsTransformer
     */
    private $associationGroupingsTransformer;

    /**
     * @var ConceptsTransformer
     */
    private $conceptsTransformer;

    /**
     * @var ItemTypesTransformer
     */
    private $itemTypesTransformer;

    /**
     * @var LicencesTransformer
     */
    private $licencesTransformer;

    /**
     * @var SubjectsTransformer
     */
    private $subjectsTransformer;

    public function __construct(
        AssociationGroupingsTransformer $associationGroupingsTransformer,
        ConceptsTransformer $conceptsTransformer,
        ItemTypesTransformer $itemTypesTransformer,
        LicencesTransformer $licencesTransformer,
        SubjectsTransformer $subjectsTransformer
    ) {
        $this->associationGroupingsTransformer = $associationGroupingsTransformer;
        $this->conceptsTransformer = $conceptsTransformer;
        $this->itemTypesTransformer = $itemTypesTransformer;
        $this->licencesTransformer = $licencesTransformer;
        $this->subjectsTransformer = $subjectsTransformer;
    }

    public function transform(CFDefinition $definitions): Definitions
    {
        $defObjs = new Definitions();

        $defObjs->associationGroupings = $this->associationGroupingsTransformer->transform($definitions->cfAssociationGroupings);
        $defObjs->concepts = $this->conceptsTransformer->transform($definitions->cfConcepts);
        $defObjs->itemTypes = $this->itemTypesTransformer->transform($definitions->cfItemTypes);
        $defObjs->licences = $this->licencesTransformer->transform($definitions->cfLicenses);
        $defObjs->subjects = $this->subjectsTransformer->transform($definitions->cfSubjects);

        return $defObjs;
    }
}
