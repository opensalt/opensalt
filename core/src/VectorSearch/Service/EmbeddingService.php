<?php

declare(strict_types=1);

namespace App\VectorSearch\Service;

use Codewithkyrian\Transformers\Pipelines\Pipeline;
use Codewithkyrian\Transformers\Transformers;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

/**
 * Service for generating text embeddings using TransformersPHP.
 */
class EmbeddingService
{
    private const MODEL_NAME = 'Xenova/all-MiniLM-L6-v2';
    private const EMBEDDING_DIMENSION = 384;
    private const MAX_INFERENCE_BATCH_SIZE = 8;

    private ?Pipeline $extractor = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/transformers_cache')]
        private string $cacheDir,
        private LoggerInterface $logger,
    ) {
        $this->initializeTransformers();
    }

    private function initializeTransformers(): void
    {
        Transformers::setup()
            ->setCacheDir($this->cacheDir)
            ->setLogger($this->logger);
    }

    /**
     * Generate embedding for a single text.
     *
     * @return list<float>
     */
    public function generateEmbedding(string $text): array
    {
        $embedding = $this->getExtractor()(
            $text,
            pooling: 'mean',
            normalize: true
        );

        if (isset($embedding[0]) && is_array($embedding[0])) {
            $embedding = $embedding[0];
        }

        if (!is_array($embedding) || self::EMBEDDING_DIMENSION !== count($embedding)) {
            throw new \RuntimeException(sprintf('Expected %d embedding values from %s, got %s.', self::EMBEDDING_DIMENSION, self::MODEL_NAME, is_array($embedding) ? (string) count($embedding) : get_debug_type($embedding)));
        }

        return $embedding;
    }

    /**
     * Generate embeddings for multiple texts (batch processing).
     *
     * @param list<string> $texts
     * @return list<list<float>>
     */
    public function generateBatchEmbeddings(array $texts): array
    {
        if ([] === $texts) {
            return [];
        }

        $allEmbeddings = [];
        foreach (array_chunk($texts, self::MAX_INFERENCE_BATCH_SIZE) as $textChunk) {
            $embeddings = $this->getExtractor()(
                $textChunk,
                pooling: 'mean',
                normalize: true
            );

            array_push(
                $allEmbeddings,
                ...$this->normalizeBatchEmbeddings($embeddings, count($textChunk))
            );
        }

        return $allEmbeddings;
    }

    /**
     * Get the embedding extractor pipeline.
     */
    private function getExtractor(): Pipeline
    {
        if (null === $this->extractor) {
            $this->logger->info('Loading embedding model', [
                'model' => self::MODEL_NAME,
                'cache_dir' => $this->cacheDir,
            ]);

            try {
                $this->extractor = pipeline(
                    'embeddings',
                    self::MODEL_NAME,
                    cacheDir: $this->cacheDir
                );
            } catch (\Throwable $exception) {
                $diagnostics = $this->buildDiagnostics();

                $this->logger->error('Failed to load embedding model', [
                    'model' => self::MODEL_NAME,
                    'diagnostics' => $diagnostics,
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                    'previous_exceptions' => $this->flattenExceptionMessages($exception->getPrevious()),
                ]);

                throw new \RuntimeException(sprintf('Failed to load embedding model %s. Diagnostics: %s. Previous errors: %s', self::MODEL_NAME, json_encode($diagnostics, JSON_THROW_ON_ERROR), json_encode($this->flattenExceptionMessages($exception->getPrevious()), JSON_THROW_ON_ERROR)), 0, $exception);
            }
        }

        return $this->extractor;
    }

    /**
     * @return array<string, bool|string>
     */
    private function buildDiagnostics(): array
    {
        $modelCachePath = $this->cacheDir.'/'.self::MODEL_NAME;

        return [
            'cache_dir' => $this->cacheDir,
            'model_cache_path' => $modelCachePath,
            'config_exists' => is_file($modelCachePath.'/config.json'),
            'tokenizer_exists' => is_file($modelCachePath.'/tokenizer.json'),
            'tokenizer_config_exists' => is_file($modelCachePath.'/tokenizer_config.json'),
            'onnx_exists' => is_file($modelCachePath.'/onnx/model_quantized.onnx'),
            'ffi_extension_loaded' => extension_loaded('FFI'),
            'ffi_enable' => (string) ini_get('ffi.enable'),
            'php_sapi' => PHP_SAPI,
        ];
    }

    /**
     * @return list<array{class: string, message: string}>
     */
    private function flattenExceptionMessages(?\Throwable $exception): array
    {
        $messages = [];

        while (null !== $exception) {
            $messages[] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ];
            $exception = $exception->getPrevious();
        }

        return $messages;
    }

    /**
     * Get the embedding dimension.
     */
    public function getDimension(): int
    {
        return self::EMBEDDING_DIMENSION;
    }

    /**
     * @return list<list<float>>
     */
    private function normalizeBatchEmbeddings(mixed $embeddings, int $expectedCount): array
    {
        if (!is_array($embeddings)) {
            throw new \RuntimeException(sprintf('Expected batch embeddings from %s to be an array, got %s.', self::MODEL_NAME, get_debug_type($embeddings)));
        }

        if (1 === $expectedCount && $this->isEmbeddingVector($embeddings)) {
            return [$this->normalizeEmbeddingVector($embeddings)];
        }

        if (count($embeddings) !== $expectedCount) {
            throw new \RuntimeException(sprintf('Expected %d embeddings from %s, got %d.', $expectedCount, self::MODEL_NAME, count($embeddings)));
        }

        $normalizedEmbeddings = [];
        foreach ($embeddings as $embedding) {
            if (!is_array($embedding)) {
                throw new \RuntimeException(sprintf('Expected each embedding from %s to be an array, got %s.', self::MODEL_NAME, get_debug_type($embedding)));
            }

            $normalizedEmbeddings[] = $this->normalizeEmbeddingVector($embedding);
        }

        return $normalizedEmbeddings;
    }

    /**
     * @param list<mixed> $embedding
     * @return list<float>
     */
    private function normalizeEmbeddingVector(array $embedding): array
    {
        if (self::EMBEDDING_DIMENSION !== count($embedding)) {
            throw new \RuntimeException(sprintf('Expected %d embedding values from %s, got %d.', self::EMBEDDING_DIMENSION, self::MODEL_NAME, count($embedding)));
        }

        return array_map(static fn (mixed $value): float => (float) $value, $embedding);
    }

    /**
     * @param list<mixed> $embedding
     */
    private function isEmbeddingVector(array $embedding): bool
    {
        return self::EMBEDDING_DIMENSION === count($embedding)
            && array_reduce(
                $embedding,
                static fn (bool $carry, mixed $value): bool => $carry && is_numeric($value),
                true
            );
    }
}
