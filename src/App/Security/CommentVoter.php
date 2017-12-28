<?php

namespace App\Security;

use JMS\DiExtraBundle\Annotation as DI;
use Salt\SiteBundle\Entity\Comment;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class CommentVoter
 *
 * @DI\Service(public=false)
 * @DI\Tag("security.voter")
 */
class CommentVoter extends Voter
{
    public const COMMENT = 'comment';
    public const VIEW = 'comment_view';
    public const UPDATE = 'comment_update';
    public const DELETE = 'comment_delete';

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
        $user = $token->getUser();

        switch ($attribute) {
            case self::COMMENT:
                return $this->canComment($user);
            case self::VIEW:
                return $this->canView($user);
            case self::UPDATE:
            case self::DELETE:
                return $this->canUpdate($user, $subject);
        }

        return false;
    }

    /**
     * Validate if a user can comment on documents and items.
     *
     * @param User $user
     *
     * @return bool
     */
    private function canComment($user): bool
    {
        return $user instanceof User;
    }

    /**
     * Validate if a user can view comments of documents and items.
     *
     * @param User $user
     *
     * @return bool
     */
    private function canView($user): bool
    {
        return true;
    }

    /**
     * @param User $user
     * @param Comment $comment
     *
     * @return bool
     */
    private function canUpdate($user, $comment): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return $comment->getUser()->getId() === $user->getId();
    }
}
