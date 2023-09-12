<?php

namespace App\Form\DTO;

use App\Form\Validator\ValidMfaCode;
use Symfony\Component\Validator\Constraints as Assert;

class MfaCodeDTO
{
    #[Assert\NotNull()]
    #[Assert\Length(exactly: 6)]
    #[ValidMfaCode]
    public string $code;
}
