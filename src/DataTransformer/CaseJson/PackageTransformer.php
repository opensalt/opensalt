<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFPackage;
use App\Entity\Framework\LsDoc;

class PackageTransformer
{
    /**
     * @var DefinitionsTransformer
     */
    private $definitionsTransformer;

    /**
     * @var DocumentTransformer
     */
    private $documentTransformer;

    /**
     * @var ItemsTransformer
     */
    private $itemsTransformer;

    /**
     * @var AssociationsTransformer
     */
    private $associationsTransformer;

    /**
     * @var RubricsTransformer
     */
    private $rubricsTransformer;

    public function __construct(
        DefinitionsTransformer $definitionsTransformer,
        DocumentTransformer $documentTransformer,
        ItemsTransformer $itemsTransformer,
        AssociationsTransformer $associationsTransformer,
        RubricsTransformer $rubricsTransformer
    ) {
        $this->definitionsTransformer = $definitionsTransformer;
        $this->documentTransformer = $documentTransformer;
        $this->itemsTransformer = $itemsTransformer;
        $this->associationsTransformer = $associationsTransformer;
        $this->rubricsTransformer = $rubricsTransformer;
    }

    public function transform(CFPackage $package): LsDoc
    {
        $definitions = $this->definitionsTransformer->transform($package->cfDefinitions);

        $doc = $this->documentTransformer->transform($package->cfDocument, $definitions);
        $items = $this->itemsTransformer->transform($package->cfItems, $doc, $definitions);
        $associations = $this->associationsTransformer->transform($package->cfAssociations, $doc, $items, $definitions);
        $rubrics = $this->rubricsTransformer->transform($package->cfRubrics, $items);

        return $doc;
    }
}
