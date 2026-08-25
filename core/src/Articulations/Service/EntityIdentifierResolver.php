<?php

declare(strict_types=1);

namespace App\Articulations\Service;

use App\Articulations\Model\EntityIdentifiers;
use App\Articulations\Model\InstitutionMatch;
use App\Entity\Framework\LsItem;
use App\Repository\Framework\LsItemRepository;

final readonly class EntityIdentifierResolver
{
    public function __construct(
        private LsItemRepository $itemRepository,
    ) {
    }

    public function findSendingInstitution(EntityIdentifiers $request): ?LsItem
    {
        return $this->findSendingInstitutionMatch($request)?->item;
    }

    public function findReceivingInstitution(EntityIdentifiers $request): ?LsItem
    {
        return $this->findReceivingInstitutionMatch($request)?->item;
    }

    public function findSendingInstitutionMatch(EntityIdentifiers $request): ?InstitutionMatch
    {
        return $this->itemRepository->findInstitutionMatchByIdentifiers($request);
    }

    public function findReceivingInstitutionMatch(EntityIdentifiers $request): ?InstitutionMatch
    {
        return $this->itemRepository->findInstitutionMatchByIdentifiers($request);
    }
}
