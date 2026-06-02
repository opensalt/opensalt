<?php

declare(strict_types=1);

namespace App\Form\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MirroredFrameworkEditDTO
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Url()]
    public string $url;
}
