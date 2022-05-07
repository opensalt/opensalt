<?php

namespace App\Form\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class OAuthCredentialDTO
{
    /**
     * @var string
     */
    #[Assert\NotNull]
    #[Assert\NotBlank]
    public $authenticationEndpoint;

    /**
     * @var string
     */
    #[Assert\NotNull]
    #[Assert\NotBlank]
    public $key;

    /**
     * @var string
     */
    #[Assert\NotNull]
    #[Assert\NotBlank]
    public $secret;
}
