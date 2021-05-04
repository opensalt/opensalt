<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFPackage;
use App\Entity\Framework\LsDoc;

class PackageTransformer
{
    public function __construct(
        private DefinitionsTransformer $definitionsTransformer,
        private DocumentTransformer $documentTransformer,
        private ItemsTransformer $itemsTransformer,
        private AssociationsTransformer $associationsTransformer,
        private RubricsTransformer $rubricsTransformer,
    ) {
    }

    public function transform(CFPackage $package): LsDoc
    {
        $definitions = $this->definitionsTransformer->transform($package->cfDefinitions);

        $doc = $this->documentTransformer->transform($package->cfDocument, $definitions);
        $items = $this->itemsTransformer->transform($package->cfItems, $doc, $definitions);
        $this->associationsTransformer->transform($package->cfAssociations, $doc, $items, $definitions);
        $this->rubricsTransformer->transform($package->cfRubrics, $items);

        return $doc;
    }
}
