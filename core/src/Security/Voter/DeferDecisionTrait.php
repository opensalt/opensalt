<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

trait DeferDecisionTrait
{
    private AccessDecisionManagerInterface $decisionManager;

    /**
     * @required
     */
    public function setDecisionManager(AccessDecisionManagerInterface $decisionManager): void
    {
        $this->decisionManager = $decisionManager;
    }

    protected function deferDecision(TokenInterface $token, array $attributes, mixed $subject = null): bool
    {
        return $this->decisionManager->decide($token, $attributes, $subject);
    }
}
