<?php

declare(strict_types=1);

namespace App\Service\Markdown;

use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Service\Markdown\Renderer\AssessmentRenderer;
use App\Service\Markdown\Renderer\CourseRenderer;
use App\Service\Markdown\Renderer\CredentialRenderer;
use App\Service\Markdown\Renderer\DefaultItemRenderer;
use App\Service\Markdown\Renderer\IdentifierRenderer;
use App\Service\Markdown\Renderer\JobRenderer;
use App\Service\Markdown\Renderer\OrganizationRenderer;
use App\Service\Markdown\Renderer\PublicKeyRenderer;

final readonly class ItemRendererLocator
{
    public function __construct(
        private DefaultItemRenderer $defaultRenderer,
        private CredentialRenderer $credentialRenderer,
        private ?CourseRenderer $courseRenderer = null,
        private ?JobRenderer $jobRenderer = null,
        private ?AssessmentRenderer $assessmentRenderer = null,
        private ?OrganizationRenderer $organizationRenderer = null,
        private ?IdentifierRenderer $identifierRenderer = null,
        private ?PublicKeyRenderer $publicKeyRenderer = null,
    ) {
    }

    public function for(LsItem $item): ItemSectionRenderer
    {
        $kind = LsItemKind::tryFrom($item->getDiscriminator()) ?? LsItemKind::Default;

        $renderer = match ($kind) {
            LsItemKind::Credential => $this->credentialRenderer,
            LsItemKind::Course => $this->courseRenderer,
            LsItemKind::Job => $this->jobRenderer,
            LsItemKind::Assessment => $this->assessmentRenderer,
            LsItemKind::Organization => $this->organizationRenderer,
            LsItemKind::Identifier => $this->identifierRenderer,
            LsItemKind::PublicKey => $this->publicKeyRenderer,
            LsItemKind::Default => $this->defaultRenderer,
        };

        return $renderer ?? $this->defaultRenderer;
    }
}
