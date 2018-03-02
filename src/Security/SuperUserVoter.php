<?php

namespace App\Security;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class SuperUserVoter
 *
 * @DI\Service(public=false)
 * @DI\Tag("security.voter")
 */
class SuperUserVoter extends Voter
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * SuperUserVoter constructor.
     *
     * @param \Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface $decisionManager
     *
     * @DI\InjectParams({
     *     "decisionManager" = @DI\Inject("security.access.decision_manager")
     * })
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        // Do not support the role check we are doing in voteOnAttribute
        if ($attribute === 'ROLE_SUPER_USER') {
            return false;
        }

        // Pass on IS_AUTHENTICATED_* checks
        if (0 === strpos($attribute, 'IS_AUTHENTICATED_')) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // ROLE_SUPER_USER can do anything
        if ($this->decisionManager->decide($token, ['ROLE_SUPER_USER'])) {
            return true;
        }

        return false;
    }
}
