<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

trait DeferDecisionTrait
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @required
     */
    public function setDecisionManager(AccessDecisionManagerInterface $decisionManager): void
    {
        $this->decisionManager = $decisionManager;
    }

    protected function deferDecision(TokenInterface $token, array $attributes, $subject = null): bool
    {
        return $this->decisionManager->decide($token, $attributes, $subject);
    }
}
