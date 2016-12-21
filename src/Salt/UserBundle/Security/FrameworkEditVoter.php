<?php

namespace Salt\UserBundle\Security;

use CftfBundle\Entity\LsDoc;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class FrameworkEditVoter
 *
 * @DI\Service(public=false)
 * @DI\Tag("security.voter")
 */
class FrameworkEditVoter extends Voter
{
    const EDIT = 'edit';

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
    protected function supports($attribute, $subject) {
        if ($attribute !== self::EDIT) {
            return false;
        }

        if (!$subject instanceof LsDoc) {
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
            // If the user is not logged in then deny access
            return false;
        }

        // This check only supports LsDoc objects
        if (!$subject instanceof LsDoc) {
            return false;
        }

        // Do not allow editing if the user is not an editor
        if (!$this->decisionManager->decide($token, ['ROLE_EDITOR'])) {
            return false;
        }

        // Allow the owner to edit the framework
        if ($subject->getUser() === $user) {
            return true;
        }

        // Allow editing if the user is a super-editor
        if ($this->decisionManager->decide($token, ['ROLE_SUPER_EDITOR'])) {
            return true;
        }

        // Check for an explicit ACL
        $docAcls = $user->getDocAcls();
        foreach ($docAcls as $acl) {
            if ($acl->getLsDoc() === $subject) {
                return 1 === $acl->getAccess();
            }
        }

        // Check if the user is in the same organization
        if ($user->getOrg() === $subject->getOrg()) {
            return true;
        }

        // Otherwise the user does not have edit rights
        return false;
    }
}
