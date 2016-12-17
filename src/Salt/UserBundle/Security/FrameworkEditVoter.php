<?php
/**
 *
 */

namespace Salt\UserBundle\Security;

use CftfBundle\Entity\LsDoc;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

        // For now, all logged in users can edit anything
        return true;
    }
}