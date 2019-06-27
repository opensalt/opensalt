<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use App\Entity\User\UserDocAcl;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class FrameworkAccessVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';

    public const FRAMEWORK = 'lsdoc';

    /**
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
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
        if (!\in_array($attribute, [static::VIEW, static::CREATE, static::EDIT, static::DELETE], true)) {
            return false;
        }

        // If the attribute is CREATE then we can handle if the subject is FRAMEWORK
        if (static::FRAMEWORK === $subject && static::CREATE === $attribute) {
            return true;
        }

        // For the other attributes, we can handle if the subject is a document
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
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        switch ($attribute) {
            case self::CREATE:
                return (static::FRAMEWORK === $subject) && $this->canCreateFramework($token);

            case self::VIEW:
                return $this->canViewFramework($subject, $token);

            case self::EDIT:
                return $this->canEditFramework($subject, $token);

            case self::DELETE:
                return $this->canDeleteFramework($subject, $token);
        }

        return false;
    }

    private function canCreateFramework(TokenInterface $token): bool
    {
        $hasRoles = $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());

        if (in_array('ROLE_EDITOR', $hasRoles, false)) {
            return true;
        }

        return false;
    }

    private function canViewFramework(LsDoc $subject, TokenInterface $token): bool
    {
        if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $subject->getAdoptionStatus()) {
            return true;
        }

        return $this->canEditFramework($subject, $token);
    }

    private function canEditFramework(LsDoc $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            // If the user is not logged in then deny access
            return false;
        }

        $hasRoles = $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());

        // Do not allow editing if the user is not an editor
        if (!in_array('ROLE_EDITOR', $hasRoles, false)) {
            return false;
        }

        // Allow editing if the user is a super-editor
        if (in_array('ROLE_SUPER_EDITOR', $hasRoles, false)) {
            return true;
        }

        // Allow the owner to edit the framework
        if ($subject->getUser() === $user) {
            return true;
        }

        // Check for an explicit ACL (could be a DENY)
        $docAcls = $user->getDocAcls();
        foreach ($docAcls as $acl) {
            if ($acl->getLsDoc() === $subject) {
                return UserDocAcl::ALLOW === $acl->getAccess();
            }
        }

        // Lastly check if the user is in the same organization
        return $user->getOrg() === $subject->getOrg();
    }

    private function canDeleteFramework(LsDoc $subject, TokenInterface $token): bool
    {
        return $this->canEditFramework($subject, $token);
    }
}
