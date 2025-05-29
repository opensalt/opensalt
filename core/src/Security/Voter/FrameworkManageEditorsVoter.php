<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, LsDoc>
 */
class FrameworkManageEditorsVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE_EDITORS = Permission::MANAGE_EDITORS;

    #[\Override]
    public function supportsAttribute(string $attribute): bool
    {
        return self::MANAGE_EDITORS === $attribute;
    }

    #[\Override]
    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, LsDoc::class, true);
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (self::MANAGE_EDITORS !== $attribute) {
            return false;
        }

        if (!$subject instanceof LsDoc) {
            return false;
        }

        return true;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // Do not allow editing of mirrored frameworks
        if (null !== $subject->getMirroredFramework()) {
            $vote?->addReason('Cannot edit mirrored frameworks.');

            return false;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            // If the user is not logged in then deny access
            $vote?->addReason('The user is not logged in.');

            return false;
        }

        // Allow the owner to manage their own framework
        if ($subject->getUser() === $user) {
            return true;
        }

        // Do not allow managing editors if the user is not an admin
        if (!$this->roleChecker->isAdmin($token)) {
            return false;
        }

        // Allow super users to manage editors
        if ($this->roleChecker->isSuperUser($token)) {
            return true;
        }

        // Lastly, check if the user is in the same organization
        return $user->getOrg() === $subject->getOrg();
    }
}
