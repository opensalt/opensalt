<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * This class represents a Flysystem file.
 *
 * The flysystem-doctrine package creates and manages the `flysystem_files` table
 * via DBAL directly. This class is NOT mapped as a Doctrine ORM entity to avoid
 * schema drift from ENUM column types that DBAL 4 cannot represent without
 * columnDefinition mismatches. The table is excluded from migration generation
 * via the `schema_filter` regex in doctrine.yaml.
 */
class FlysystemFile
{
    private int $id;

    private string $path;

    private string $type;

    /** @var resource|null */
    private $contents;

    private int $size = 0;

    private int $level;

    private ?string $mimeType = null;

    private string $visibility = 'public';

    private int $timestamp;
}
