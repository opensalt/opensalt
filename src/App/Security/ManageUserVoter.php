<?php

namespace App\Security;

use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class StandardVoter
 *
 * @DI\Service(public=false)
 * @DI\Tag("security.voter")
 */
class ManageUserVoter extends Voter
{
    public const MANAGE = 'manage';

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
    protected function supports($attribute, $subject){
        if (self::MANAGE !== $attribute) {
            return false;
        }

        if (!$subject instanceof User && $subject !== 'users') {
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
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::MANAGE:
                if ($subject === 'users') {
                    return $this->canManageUsers($token);
                }

                return $this->canManageUser($subject, $user);
            default:
                return false;
        }
    }

    /**
     * Validate if a user can create a standard.
     *
     * @param User $targetUser
     * @param User $user
     *
     * @return bool true if the current user can manage a specific user
     */
    private function canManageUser(User $targetUser = null, User $user): bool
    {
        if (null === $targetUser) {
            return false;
        }

        if ($targetUser->getOrg()->getId() === $user->getOrg()->getId()) {
            return true;
        }

        return false;
    }

    /**
     * Validate if a user can manage users
     *
     * @param TokenInterface $token
     *
     * @return bool true if the current user can manage users
     */
    private function canManageUsers(TokenInterface $token): bool
    {
        // ROLE_ADMIN can manage users
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return false;
    }
}
