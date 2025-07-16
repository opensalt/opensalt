<?php

declare(strict_types=1);

namespace App\Form\DTO;

use App\Entity\Framework\Mirror\OAuthCredential;
use App\Entity\Framework\Mirror\Server;
use Symfony\Component\Validator\Constraints as Assert;

class MirroredServerDTO
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: true)]
    public ?string $url = null;

    #[Assert\NotNull]
    #[Assert\Choice(choices: [Server::TYPE_CASE_1_0, Server::TYPE_CASE_1_1])]
    public ?string $serverType = Server::TYPE_CASE_1_0;

    #[Assert\NotNull]
    public bool $autoAddFoundFrameworks = false;

    public ?OAuthCredential $credentials = null;

    public string $status = Server::STATUS_ACTIVE;
}
