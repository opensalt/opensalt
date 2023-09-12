<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ItemVoter extends Voter
{
    use DeferDecisionTrait;
    use RoleCheckTrait;

    final public const ADD_TO = Permission::ITEM_ADD_TO;
    final public const EDIT = Permission::ITEM_EDIT;

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [self::ADD_TO, self::EDIT], true);
    }

    public function supportsType(string $subjectType): bool
    {
        if ('null' === $subjectType) {
            return true;
        }

        if (is_a($subjectType, LsDoc::class, true)) {
            return true;
        }

        return is_a($subjectType, LsItem::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            // User can add to a specific doc or "some doc"
            self::ADD_TO => $subject instanceof LsDoc || null === $subject,
            self::EDIT => $subject instanceof LsItem,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
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
