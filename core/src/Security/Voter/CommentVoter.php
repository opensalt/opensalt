<?php

namespace App\Security\Voter;

use App\Entity\Comment\Comment;
use App\Entity\User\User;
use App\Security\Feature;
use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    use RoleCheckTrait;
    use FeatureCheckTrait;

    final public const COMMENT = Permission::COMMENT_ADD;
    final public const VIEW = Permission::COMMENT_VIEW;
    final public const UPDATE = Permission::COMMENT_UPDATE;
    final public const DELETE = Permission::COMMENT_DELETE;

    public function supportsAttribute(string $attribute): bool
    {
        if (!$this->hasActiveFeature(Feature::COMMENTS)) {
            // No support for comments if the feature is not enabled
            return false;
        }

        return \in_array($attribute, [self::UPDATE, self::DELETE, self::COMMENT, self::VIEW], true);
    }

    public function supportsType(string $subjectType): bool
    {
        if ('null' === $subjectType) {
            return true;
        }

        return is_a($subjectType, Comment::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->hasActiveFeature(Feature::COMMENTS)) {
            // No support for comments if the feature is not enabled
            return false;
        }

        return match ($attribute) {
            self::UPDATE, self::DELETE => $subject instanceof Comment,
            self::COMMENT, self::VIEW => true,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // All users (including anonymous) can view comments
        if (self::VIEW === $attribute) {
            return $this->canView();
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            // If the user is not logged in then deny access
            return false;
        }

        return match ($attribute) {
            self::COMMENT => $this->canComment(),
            self::UPDATE, self::DELETE => $this->canUpdate($user, $subject, $token),
            default => false,
        };
    }

    /**
     * All users (including anonymous) can view comments.
     */
    private function canView(): bool
    {
        return true;
    }

    /**
     * All logged-in users can comment.
     */
    private function canComment(): bool
    {
        return true;
    }

    private function canUpdate(User $user, Comment $comment, TokenInterface $token): bool
    {
        if ($this->roleChecker->isSuperUser($token)) {
            return true;
        }

        return $comment->getUser()->getId() === $user->getId();
    }
}
