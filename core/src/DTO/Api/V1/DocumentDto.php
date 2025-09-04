<?php

declare(strict_types=1);

namespace App\DTO\Api\V1;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class DocumentDto
{
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'uuid',
        pattern: '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The identifier is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The identifier is required', groups: ['view'])]
    #[Map('identifier')]
    public ?UuidInterface $identifier = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'uri',
        nullable: true,
    )]
    #[Assert\NotNull(message: 'The uri is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The uri is required', groups: ['view'])]
    #[Map('uri')]
    public ?string $uri = null;

    #[Groups(['view'])]
    #[SerializedName('CFPackageURI')]
    #[Assert\NotNull(message: 'The link is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The link is required', groups: ['view'])]
    public ?LinkURI $cfPackageURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[Assert\Choice([null, '1.1'], groups: ['create', 'update', 'view'])]
    #[Map('caseVersion')]
    public ?string $caseVersion = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('frameworkType.title')]
    public ?string $frameworkType = null;

    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotBlank(message: 'Cannot be an empty string', groups: ['create', 'update', 'view'])]
    #[Map('creator')]
    public string $creator;

    #[Groups(['create', 'update', 'view'])]
    #[Assert\NotBlank(message: 'Cannot be an empty string', groups: ['create', 'update', 'view'])]
    #[Map('title')]
    public string $title;

    #[Groups(['view'])]
    #[Assert\NotNull(message: 'The date is required', groups: ['view'])]
    #[Assert\NotBlank(message: 'The date is required', groups: ['view'])]
    #[Map('changedAt')]
    public \DateTimeInterface $lastChangeDateTime;

    #[Groups(['create', 'update', 'view'])]
    #[Map('officialUri')]
    public ?string $officialSourceURL = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('publisher')]
    public ?string $publisher = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('description')]
    public ?string $description = null;

    /** @var string[]|string|null */
    #[Groups(['create', 'update', 'view'])]
    #[Map('subject')]
    public string|array|null $subject = null;

    /** @var LinkURI[] */
    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: LinkURI::class)),
        uniqueItems: true,
    )]
    #[Assert\All(new Assert\Type(LinkURI::class))]
    #[Map('subjects')]
    public ?array $subjectURI = [];

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        description: 'Language in browser locale supported format (RFC 5646).',
        example: 'en',
    )]
    #[Map('language')]
    public ?string $language = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('version')]
    public ?string $version = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('adoptionStatus')]
    public ?string $adoptionStatus = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'date',
        nullable: true,
    )]
    #[Map('statusStart')]
    public ?\DateTimeInterface $statusStartDate = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'string',
        format: 'date',
        nullable: true,
    )]
    #[Map('statusEnd')]
    public ?\DateTimeInterface $statusEndDate = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('licence.uri')]
    public ?LinkURI $licenseURI = null;

    #[Groups(['create', 'update', 'view'])]
    #[Map('note')]
    public ?string $notes = null;

    #[Groups(['create', 'update', 'view'])]
    #[OA\Property(
        type: 'object',
        nullable: true,
        additionalProperties: true,
    )]
    #[Map('extensions')]
    public ?array $extensions = null;

    public function __construct()
    {
        $this->lastChangeDateTime = new \DateTimeImmutable();
    }
}
