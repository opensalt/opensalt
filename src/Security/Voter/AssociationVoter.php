<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AssociationVoter extends Voter
{
    public const ADD_TO = 'add-association-to';
    public const CREATE = 'create';
    public const EDIT = 'edit';

    public const ASSOCIATION = 'lsassociation';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

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
        if (!\in_array($attribute, [self::ADD_TO, self::CREATE, self::EDIT], true)) {
            return false;
        }

        switch ($attribute) {
            case self::ADD_TO:
                // User can add to a specific doc or "some doc"
                return $subject instanceof LsDoc || $subject instanceof LsItem || null === $subject;
            case self::CREATE:
                // User can create an association
                return static::ASSOCIATION === $subject;
            case self::EDIT:
                // User can edit the LsAssociation
                return $subject instanceof LsAssociation;
            default:
                return false;
        }
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

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ADD_TO:
                return $this->canAddTo($subject, $token);
            case self::CREATE:
                return (static::ASSOCIATION === $subject) && $this->canCreate($token);
            case self::EDIT:
                return $this->canEdit($subject, $token);
            default:
                return false;
        }
    }

    /**
     * Validate if a user can add a standard to a document.
     */
    private function canAddTo($subject, TokenInterface $token): bool
    {
        // Check if the user can edit the document
        if ($subject instanceof LsDoc) {
            return $this->decisionManager->decide($token, [FrameworkAccessVoter::EDIT], $subject);
        }

        // Check if the user can edit the document the item is part of
        if ($subject instanceof LsItem) {
            return $this->decisionManager->decide($token, [FrameworkAccessVoter::EDIT], $subject->getLsDoc());
        }

        // Allow if the user can edit "some" document, i.e. is an editor
        if ($this->canCreate($token)) {
            return true;
        }

        return false;
    }

    /**
     * Validate if a user can create an association.
     */
    private function canCreate(TokenInterface $token): bool
    {
        // Allow if the user is an editor
        if ($this->decisionManager->decide($token, ['ROLE_EDITOR'])) {
            return true;
        }

        return false;
    }

    /**
     * Validate if a user can edit an association.
     */
    private function canEdit(LsAssociation $association, TokenInterface $token): bool
    {
        if (false === $this->canAddTo($association->getLsDoc(), $token)) {
            // Cannot add associations to the framework
            return false;
        }

        if (!$association->canEdit()) {
            // The association is not editable
            return false;
        }

        // Allow editing of an association if the user can edit the document
        if ($this->decisionManager->decide($token, [FrameworkAccessVoter::EDIT], $association->getLsDoc())) {
            return true;
        }

        return false;
    }
}
