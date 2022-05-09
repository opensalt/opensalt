<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use App\Entity\User\UserDocAcl;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FrameworkAccessVoter extends Voter
{
    use RoleCheckTrait;

    final public const LIST = 'list'; // User can see the framework in a list
    final public const VIEW = 'view';
    final public const EDIT = 'edit';
    final public const DELETE = 'delete';
    final public const CREATE = 'create';

    final public const FRAMEWORK = 'lsdoc';

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [static::LIST, static::VIEW, static::CREATE, static::EDIT, static::DELETE], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, ['string', LsDoc::class], true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [static::LIST, static::VIEW, static::CREATE, static::EDIT, static::DELETE], true)) {
            return false;
        }

        // If the attribute is CREATE then we can handle if the subject is FRAMEWORK
        if (static::FRAMEWORK === $subject && static::CREATE === $attribute) {
            return true;
        }

        if ('all_frameworks' === $subject && static::EDIT === $attribute) {
            return true;
        }

        // For the other attributes the subject must be a document
        return $subject instanceof LsDoc;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return match ($attribute) {
            self::CREATE => (static::FRAMEWORK === $subject) && $this->canCreateFramework($token),
            self::LIST => $this->canListFramework($subject, $token),
            self::VIEW => $this->canViewFramework($subject, $token),
            self::EDIT => $this->canEditFramework($subject, $token),
            self::DELETE => $this->canDeleteFramework($subject, $token),
            default => false,
        };
    }

    private function canCreateFramework(TokenInterface $token): bool
    {
        if ($this->roleChecker->isEditor($token)) {
            return true;
        }

        return false;
    }

    private function canListFramework(LsDoc $subject, TokenInterface $token): bool
    {
        if (LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT !== $subject->getAdoptionStatus()) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            // If the user is not logged in then they can't see private frameworks
            return false;
        }

        // Allow users to view private frameworks of their org
        if ($user->getOrg() === $subject->getOrg()) {
            return true;
        }

        // Editors can see all mirrored frameworks in the list
        if (null !== $subject->getMirroredFramework()) {
            return $this->roleChecker->isEditor($token);
        }

        return $this->canEditFramework($subject, $token);
    }

    private function canViewFramework(LsDoc $subject, TokenInterface $token): bool
    {
        // Anyone can view a framework if they know about it
        return true;
    }

    private function canEditFramework(mixed $subject, TokenInterface $token): bool
    {
        // Do not allow editing if the framework is mirrored
        if ($subject instanceof LsDoc && null !== $subject->getMirroredFramework() && $subject->getMirroredFramework()->isInclude()) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            // If the user is not logged in then deny access
            return false;
        }

        // Do not allow editing if the user is not an editor
        if (!$this->roleChecker->isEditor($token)) {
            return false;
        }

        // Allow editing if the user is a super-editor
        if ($this->roleChecker->isSuperEditor($token)) {
            return true;
        }

        if (!$subject instanceof LsDoc) {
            // If the subject is not a document then do not allow editing
            return false;
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
