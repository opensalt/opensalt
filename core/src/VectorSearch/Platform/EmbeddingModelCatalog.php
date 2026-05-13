<?php

declare(strict_types=1);

namespace App\VectorSearch\Platform;

use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;

class EmbeddingModelCatalog extends AbstractModelCatalog
{
    protected const MODELS = [
        'Xenova/all-MiniLM-L6-v2' => [
            'class' => EmbeddingsModel::class,
            'capabilities' => [
                Capability::EMBEDDINGS,
            ],
        ],
    ];

    public function __construct()
    {
        $this->models = self::MODELS;
    }
}
