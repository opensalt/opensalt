<?php

declare(strict_types=1);

namespace App\Form\DTO;

use App\Entity\Framework\Mirror\OAuthCredential;
use Symfony\Component\Validator\Constraints as Assert;

class MirroredFrameworkDTO
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Url()]
    public string $url;

    public bool $visible = false;

    public ?OAuthCredential $credentials = null;
}
