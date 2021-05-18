<?php

namespace App\Security\Voter;

use App\Entity\Comment\Comment;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    use RoleCheckTrait;
    use FeatureCheckTrait;

    public const COMMENT = 'comment';
    public const VIEW = 'comment_view';
    public const UPDATE = 'comment_update';
    public const DELETE = 'comment_delete';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        if (!$this->hasActiveFeature('comments')) {
            // No support for comments if the feature is not enabled
            return false;
        }

        switch ($attribute) {
            case self::UPDATE:
            case self::DELETE:
                if ($subject instanceof Comment) {
                    return true;
                }
                break;

            case self::COMMENT:
            case self::VIEW:
                return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
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

        switch ($attribute) {
            case self::VIEW:
                return $this->canView();
            case self::COMMENT:
                return $this->canComment();
            case self::UPDATE:
            case self::DELETE:
                return $this->canUpdate($user, $subject, $token);
            default:
                return false;
        }
    }

    /**
     * All users (including anonymous) can view comments.
     */
    private function canView(): bool
    {
        return true;
    }

    /**
     * All logged in users can comment.
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
