<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * This class represents a Flysystem file and is used for Doctrine to generate the table schema.
 *
 * The flysystem-doctrine package is used to actually work with the files.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'flysystem_files')]
#[ORM\UniqueConstraint(name: 'path_unique', columns: ['path'])]
class FlysystemFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private int $id; // @phpstan-ignore property.unused

    #[ORM\Column()]
    private string $path; // @phpstan-ignore property.unused

    #[ORM\Column(type: Types::STRING, columnDefinition: "ENUM('dir', 'file')")]
    private string $type; // @phpstan-ignore property.unused

    /**
     * @var resource|null
     */
    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $contents; // @phpstan-ignore property.unused

    #[ORM\Column(options: ['default' => 0])]
    private int $size = 0; // @phpstan-ignore property.onlyWritten

    #[ORM\Column()]
    private int $level; // @phpstan-ignore property.unused

    #[ORM\Column(name: 'mimetype', length: 127, nullable: true)]
    private ?string $mimeType = null; // @phpstan-ignore property.unused

    #[ORM\Column(type: Types::STRING, options: ['default' => 'public'], columnDefinition: "ENUM('public', 'private')")]
    private string $visibility = 'public'; // @phpstan-ignore property.unused

    #[ORM\Column(options: ['default' => 0])]
    private int $timestamp; // @phpstan-ignore property.unused
}
