<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use Symfony\Component\Validator\Constraints as Assert;

class AddAccessGroupByNameCommand extends BaseCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $accessGroupName,
    ) {
    }

    public function getAccessGroupName(): string
    {
        return $this->accessGroupName;
    }
}
