<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AssociationVoter extends Voter
{
    use RoleCheckTrait;
    use DeferDecisionTrait;

    final public const ADD_TO = 'add-association-to';
    final public const CREATE = 'create';
    final public const EDIT = 'edit';

    final public const ASSOCIATION = 'lsassociation';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
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
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
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
     *
     * @param mixed $subject
     */
    private function canAddTo($subject, TokenInterface $token): bool
    {
        // Check if the user can edit the document
        if ($subject instanceof LsDoc) {
            return $this->deferDecision($token, [FrameworkAccessVoter::EDIT], $subject);
        }

        // Check if the user can edit the document the item is part of
        if ($subject instanceof LsItem) {
            return $this->deferDecision($token, [FrameworkAccessVoter::EDIT], $subject->getLsDoc());
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
        return $this->roleChecker->isEditor($token);
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
        if ($this->deferDecision($token, [FrameworkAccessVoter::EDIT], $association->getLsDoc())) {
            return true;
        }

        return false;
    }
}
