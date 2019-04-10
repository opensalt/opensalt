<?php

namespace App\Security\Voter;

use App\Entity\User\User;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ItemVoter extends Voter
{
    public const ADD_TO = 'add-standard-to';
    public const EDIT = 'edit';

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

            case self::EDIT:
                return $this->canEdit($subject, $token);
        }

        return false;
    }

    /**
     * Validate if a user can add a standard to a document.
     */
    private function canAddTo(?LsDoc $lsDoc, TokenInterface $token): bool
    {
        if (null !== $lsDoc) {
            // Check if the user can edit the document
            if (!$this->decisionManager->decide($token, [FrameworkAccessVoter::EDIT], $lsDoc)) {
                return false;
            }
        }

        // Allow if the user can edit "some" document, i.e. is an editor
        if ($this->decisionManager->decide($token, ['ROLE_EDITOR'])) {
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
        if ($this->decisionManager->decide($token, [FrameworkAccessVoter::EDIT], $item->getLsDoc())) {
            return true;
        }

        return false;
    }
}
