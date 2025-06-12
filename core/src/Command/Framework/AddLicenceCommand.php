<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefLicence;
use Symfony\Component\Validator\Constraints as Assert;

class AddLicenceCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDefLicence::class)]
        #[Assert\NotNull]
        private readonly LsDefLicence $licence,
    ) {
    }

    public function getLicence(): LsDefLicence
    {
        return $this->licence;
    }
}
