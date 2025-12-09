<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\DTO\ItemType\AssessmentDto;
use App\DTO\ItemType\CourseDto;
use App\DTO\ItemType\CredentialDto;
use App\DTO\ItemType\IdentifierDto;
use App\DTO\ItemType\ItemTypeInterface;
use App\DTO\ItemType\JobDto;
use App\DTO\ItemType\OrganizationDto;
use App\DTO\ItemType\PublicKeyDto;

use function Symfony\Component\String\u;

enum LsItemKind: int
{
    case Default = 0;
    case Job = 1;
    case Course = 2;
    case Assessment = 3;
    case Credential = 4;
    case Organization = 5;
    case Identifier = 6;
    case PublicKey = 7;

    public const array TYPES = [
        'default' => self::Default->value,
        'job' => self::Job->value,
        'course' => self::Course->value,
        'assessment' => self::Assessment->value,
        'credential' => self::Credential->value,
        'organization' => self::Organization->value,
        'identifier' => self::Identifier->value,
        'public_key' => self::PublicKey->value,
    ];

    /** @var array<int, class-string> */
    public const array DTO = [
        0 => LsItem::class,
        1 => JobDto::class,
        2 => CourseDto::class,
        3 => AssessmentDto::class,
        4 => CredentialDto::class,
        5 => OrganizationDto::class,
        6 => IdentifierDto::class,
        7 => PublicKeyDto::class,
    ];

    public static function tryFromName(?string $name): self
    {
        return self::tryFrom(self::TYPES[u($name ?? 'default')->snake()->toString()] ?? 0) ?? self::Default;
    }

    /**
     * @return class-string<ItemTypeInterface>
     */
    public static function dtoFromValue(?int $discriminator = null): string
    {
        $kind = self::tryFrom($discriminator ?? 0) ?? self::Default;

        return $kind->dto();
    }

    /**
     * @param class-string $dtoClass
     */
    public static function fromDtoClass(string $dtoClass): self
    {
        return self::tryFrom(array_search($dtoClass, self::DTO, true)) ?? self::Default;
    }

    /**
     * @return class-string<ItemTypeInterface>
     */
    public function dto(): string
    {
        return self::DTO[$this->value];
    }

    public function objectType(): string
    {
        if (self::Default === $this) {
            return 'item';
        }

        return u($this->name)->snake()->toString();
    }
}
