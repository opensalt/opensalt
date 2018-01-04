<?php

namespace Salt\UserBundle\Security;

use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class StandardVoter
 *
 * @DI\Service(public=false)
 * @DI\Tag("security.voter")
 */
class StandardVoter extends Voter
{
    public const ADD_TO = 'add-standard-to';
    public const EDIT = 'edit';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * SuperUserVoter constructor.
     *
     * @param \Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface $decisionManager
     *
     * @DI\InjectParams({
     *     "decisionManager" = @DI\Inject("security.access.decision_manager")
     * })
     */
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
        if (!in_array($attribute, [self::ADD_TO, self::EDIT], true)) {
            return false;
        }

        switch ($attribute) {
            case self::ADD_TO:
                // User can add to a specific doc or "some doc"
                if ($subject instanceof LsDoc || $subject === null) {
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
                break;

            case self::EDIT:
                return $this->canEdit($subject, $token);
                break;
        }

        return false;
    }

    /**
     * Validate if a user can add a standard to a document.
     *
     * @param LsDoc|null $lsDoc
     * @param TokenInterface $token
     *
     * @return bool
     */
    private function canAddTo(LsDoc $lsDoc = null, TokenInterface $token)
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

    /*
    * Validate if a user can edit a standard.
    *
     * @param LsItem $item
     * @param TokenInterface $token
     *
     * @return bool
     */
    private function canEdit(LsItem $item, TokenInterface $token)
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
