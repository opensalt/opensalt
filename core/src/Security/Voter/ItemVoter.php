<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ItemVoter extends Voter
{
    use DeferDecisionTrait;
    use RoleCheckTrait;

    final public const ADD_TO = 'add-standard-to';
    final public const EDIT = 'edit';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        if (!\in_array($attribute, [self::ADD_TO, self::EDIT], true)) {
            return false;
        }

        switch ($attribute) {
            case self::ADD_TO:
                // User can add to a specific doc or "some doc"
                if ($subject instanceof LsDoc || null === $subject) {
                    return true;
                }
                break;

            case self::EDIT:
                // User can edit the LsItem
                if ($subject instanceof LsItem) {
                    return true;
                }
        }

        return false;
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

        return match ($attribute) {
            self::ADD_TO => $this->canAddTo($subject, $token),
            self::EDIT => $this->canEdit($subject, $token),
            default => false,
        };
    }

    /**
     * Validate if a user can add a standard to a document.
     */
    private function canAddTo(?LsDoc $lsDoc, TokenInterface $token): bool
    {
        // Check if the user can edit the document
        if ((null !== $lsDoc) && !$this->deferDecision($token, [FrameworkAccessVoter::EDIT], $lsDoc)) {
            return false;
        }

        // Allow if the user can edit "some" document, i.e. is an editor
        if ($this->roleChecker->isEditor($token)) {
            return true;
        }

        return false;
    }

    /**
     * Validate if a user can edit a standard.
     */
    private function canEdit(LsItem $item, TokenInterface $token): bool
    {
        if (!$item->getLsDoc()->canEdit()) {
            // The framework is not editable
            return false;
        }

        if (!$item->canEdit()) {
            // The item is not editable
            return false;
        }

        // Allow editing of an item if the user can edit the document
        if ($this->deferDecision($token, [FrameworkAccessVoter::EDIT], $item->getLsDoc())) {
            return true;
        }

        return false;
    }
}
