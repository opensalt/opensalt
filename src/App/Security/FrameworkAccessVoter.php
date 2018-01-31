<?php

namespace App\Security;

use CftfBundle\Entity\LsDoc;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\UserDocAcl;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class FrameworkEditVoter
 *
 * @DI\Service(public=false)
 * @DI\Tag("security.voter")
 */
class FrameworkAccessVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

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
        if (!\in_array($attribute, [static::VIEW, static::EDIT, static::DELETE], true)) {
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
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // This check only supports LsDoc objects
        if (!$subject instanceof LsDoc) {
            return false;
        }

        if (self::VIEW !== $attribute && !$token->getUser() instanceof User) {
            // If the user is not logged in then deny access
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canViewFramework($subject, $token);
                break;

            case self::EDIT:
                return $this->canEditFramework($subject, $token);
                break;

            case self::DELETE:
                return $this->canDeleteFramework($subject, $token);
                break;
        }

        return false;
    }

    private function canViewFramework(LsDoc $subject, TokenInterface $token)
    {
        if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $subject->getAdoptionStatus()) {
            return true;
        }

        return $this->canEditFramework($subject, $token);
    }

    private function canEditFramework(LsDoc $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            // If the user is not logged in then deny access
            return false;
        }

        // Allow editing if the user is a super-editor
        if ($this->decisionManager->decide($token, ['ROLE_SUPER_EDITOR'])) {
            return true;
        }

        // Do not allow editing if the user is not an editor
        if (!$this->decisionManager->decide($token, ['ROLE_EDITOR'])) {
            return false;
        }

        // Allow the owner to edit the framework
        if ($subject->getUser() === $user) {
            return true;
        }

        // Check for an explicit ACL
        $docAcls = $user->getDocAcls();
        foreach ($docAcls as $acl) {
            if ($acl->getLsDoc() === $subject) {
                return UserDocAcl::ALLOW === $acl->getAccess();
            }
        }

        // Check if the user is in the same organization
        if ($user->getOrg() === $subject->getOrg()) {
            return true;
        }

        // Otherwise the user does not have edit rights
        return false;
    }

    private function canDeleteFramework(LsDoc $subject, TokenInterface $token)
    {
        return $this->canEditFramework($subject, $token);
    }
}
