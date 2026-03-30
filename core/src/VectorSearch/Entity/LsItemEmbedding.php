<?php

declare(strict_types=1);

namespace App\VectorSearch\Entity;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Repository\LsItemEmbeddingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_item_embedding')]
#[ORM\UniqueConstraint(name: 'uniq_ls_item_embedding_ls_item_id', columns: ['ls_item_id'])]
#[ORM\Entity(repositoryClass: LsItemEmbeddingRepository::class)]
#[ORM\Index(name: 'idx_ls_item_id', columns: ['ls_item_id'])]
#[ORM\Index(name: 'idx_binary_code', columns: ['binary_code'])]
class LsItemEmbedding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LsItem::class, inversedBy: 'embeddings')]
    #[ORM\JoinColumn(name: 'ls_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private LsItem $lsItem;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    /**
     * @var list<float>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $vector = null;

    /**
     * @var list<float>|null
     */
    #[ORM\Column(name: 'normalized_vector', type: Types::JSON, nullable: true)]
    private ?array $normalizedVector = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $magnitude = null;

    #[ORM\Column(name: 'binary_code', type: Types::BINARY, length: 48, nullable: true)]
    private ?string $binaryCode = null;

    #[ORM\Column(name: 'bc_seg_0', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg0 = null;

    #[ORM\Column(name: 'bc_seg_1', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg1 = null;

    #[ORM\Column(name: 'bc_seg_2', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg2 = null;

    #[ORM\Column(name: 'bc_seg_3', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg3 = null;

    #[ORM\Column(name: 'bc_seg_4', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg4 = null;

    #[ORM\Column(name: 'bc_seg_5', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg5 = null;

    #[ORM\Column(name: 'bc_seg_6', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg6 = null;

    #[ORM\Column(name: 'bc_seg_7', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg7 = null;

    #[ORM\Column(name: 'bc_seg_8', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg8 = null;

    #[ORM\Column(name: 'bc_seg_9', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg9 = null;

    #[ORM\Column(name: 'bc_seg_10', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg10 = null;

    #[ORM\Column(name: 'bc_seg_11', type: 'integer', options: ['unsigned' => true], nullable: true, insertable: false, updatable: false)]
    private ?int $bcSeg11 = null;

    #[ORM\Column(name: 'is_leaf_node', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isLeafNode = false;

    #[ORM\Column(name: 'source_hierarchy_updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sourceHierarchyUpdatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(LsItem $lsItem, ?string $text = null)
    {
        $this->lsItem = $lsItem;
        $this->text = $text;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setLsItem(LsItem $lsItem): self
    {
        $this->lsItem = $lsItem;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLsItem(): LsItem
    {
        return $this->lsItem;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @param list<float> $vector
     * @param list<float> $normalizedVector
     */
    public function setVectorData(array $vector, array $normalizedVector, float $magnitude, string $binaryCode): self
    {
        $this->vector = $vector;
        $this->normalizedVector = $normalizedVector;
        $this->magnitude = $magnitude;
        $this->binaryCode = $binaryCode;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isLeafNode(): bool
    {
        return $this->isLeafNode;
    }

    public function setIsLeafNode(bool $isLeafNode): self
    {
        $this->isLeafNode = $isLeafNode;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSourceHierarchyUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->sourceHierarchyUpdatedAt;
    }

    public function setSourceHierarchyUpdatedAt(?\DateTimeImmutable $sourceHierarchyUpdatedAt): self
    {
        $this->sourceHierarchyUpdatedAt = $sourceHierarchyUpdatedAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function hasVectorData(): bool
    {
        return null !== $this->vector
            && null !== $this->normalizedVector
            && null !== $this->magnitude
            && null !== $this->binaryCode;
    }

    /**
     * @return list<float>|null
     */
    public function getNormalizedVector(): ?array
    {
        return $this->normalizedVector;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getBcSeg0(): ?int
    {
        return $this->bcSeg0;
    }

    public function getBcSeg1(): ?int
    {
        return $this->bcSeg1;
    }

    public function getBcSeg2(): ?int
    {
        return $this->bcSeg2;
    }

    public function getBcSeg3(): ?int
    {
        return $this->bcSeg3;
    }

    public function getBcSeg4(): ?int
    {
        return $this->bcSeg4;
    }

    public function getBcSeg5(): ?int
    {
        return $this->bcSeg5;
    }

    public function getBcSeg6(): ?int
    {
        return $this->bcSeg6;
    }

    public function getBcSeg7(): ?int
    {
        return $this->bcSeg7;
    }

    public function getBcSeg8(): ?int
    {
        return $this->bcSeg8;
    }

    public function getBcSeg9(): ?int
    {
        return $this->bcSeg9;
    }

    public function getBcSeg10(): ?int
    {
        return $this->bcSeg10;
    }

    public function getBcSeg11(): ?int
    {
        return $this->bcSeg11;
    }

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
