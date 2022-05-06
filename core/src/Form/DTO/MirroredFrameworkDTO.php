<?php

namespace App\Form\DTO;

use App\Entity\Framework\Mirror\OAuthCredential;
use Symfony\Component\Validator\Constraints as Assert;

class MirroredFrameworkDTO
{
    /**
     * @var string
     */
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Url]
    public $url;

    /**
     * @var OAuthCredential|null
     */
    public $credentials;
}
