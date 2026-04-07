<?php

declare(strict_types=1);

namespace App\VectorSearch\Platform;

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

class EmbeddingModelCatalog extends AbstractModelCatalog
{
    protected const MODELS = [
        'Xenova/all-MiniLM-L6-v2' => [
            'class' => Model::class,
            'capabilities' => [
                Capability::EMBEDDINGS,
            ],
        ],
    ];
}
