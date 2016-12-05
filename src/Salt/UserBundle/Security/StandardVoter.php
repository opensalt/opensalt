<?php

namespace Salt\UserBundle\Security;

use JMS\DiExtraBundle\Annotation as DI;
use Salt\UserBundle\Entity\User;
use Salt\CftfBundle\Entity\LsItem;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class StandardVoter
 * @package Salt\UserBundle\Security
 *
 * @DI\Service(public=false)
 * @DI\Tag("security.voter")
 */
class StandardVoter extends Voter
{
  const CREATE = 'create';
  const EDIT = 'edit';
  const ITEM = 'lsitem';

  /**
   * Determines if the attribute and subject are supported by this voter.
   *
   * @param string $attribute An attribute
   * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
   *
   * @return bool True if the attribute and subject are supported, false otherwise
   */
  protected function supports($attribute, $subject){
    if (!in_array($attribute, array(self::CREATE, self::EDIT))) {
      return false;
    }

    if ($subject !== self::ITEM) {
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
      return false;
    }

    $item = $subject;

    switch($attribute) {
      case self::CREATE:
        return $this->canCreate($item, $user);
      case self::EDIT:
        return $this->canEdit($item, $user);
    }

    return false;

  }

  /**
   * Validate if a user can create a standard.
   *
   * @param String $item
   * @param User $user
   *
   * @return bool
   */
  private function canCreate($item, User $user){
    return true;
  }

  /*
   * Validate if a user can edit a standard.
   *
   * @param LsItem $item
   * @param User $user
   *
   * @return bool
   */
  private function canEdit($item, User $user){
    return true;
  }
}
