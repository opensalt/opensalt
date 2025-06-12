<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefLicence;

class UpdateLicenceCommand extends BaseCommand
{
    public function __construct(private readonly LsDefLicence $licence)
    {
    }

    public function getLicence(): LsDefLicence
    {
        return $this->licence;
    }
}
