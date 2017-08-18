<?php

namespace Salt\UserBundle\Security;

use JMS\DiExtraBundle\Annotation as DI;
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
    const COMMENT = 'comment';
    const VIEW = 'view_comment';

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
        if (!in_array($attribute, [self::COMMENT, self::VIEW], true)) {
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
        $user = $token->getUser();

        switch ($attribute) {
            case self::COMMENT:
                return $this->canComment($user);
            case self::VIEW:
                return $this->canView($user);
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
    private function canComment($user)
    {
        if ($user instanceof User) {
            return true;
        }

        return false;
    }

    /**
     * Validate if a user can view comments of documents and items.
     *
     * @param User $user
     *
     * @return bool
     */
    private function canView($user)
    {
        return true;
    }
}
