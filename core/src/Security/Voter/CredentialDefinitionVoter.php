<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Domain\Credential\Entity\CredentialDefinition;
use App\Entity\User\User;
use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, CredentialDefinition|null>
 */
class CredentialDefinitionVoter extends Voter
{
    use RoleCheckTrait;

    final public const string LIST = Permission::CREDENTIAL_DEF_LIST; // User can see the credential in a list
    final public const string VIEW = Permission::CREDENTIAL_DEF_VIEW;
    final public const string EDIT = Permission::CREDENTIAL_DEF_EDIT;
    final public const string EDIT_ALL = Permission::CREDENTIAL_DEF_EDIT_ALL;
    final public const string DELETE = Permission::CREDENTIAL_DEF_DELETE;
    final public const string CREATE = Permission::CREDENTIAL_DEF_CREATE;

    #[\Override]
    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [static::LIST, static::VIEW, static::CREATE, static::EDIT, static::EDIT_ALL, static::DELETE], true);
    }

    #[\Override]
    public function supportsType(string $subjectType): bool
    {
        if ('null' === $subjectType) {
            return true;
        }

        return is_a($subjectType, CredentialDefinition::class, true);
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::CREATE, self::EDIT_ALL, self::LIST => null === $subject,
            self::VIEW, self::EDIT, self::DELETE => $subject instanceof CredentialDefinition,
            default => false,
        };
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return match ($attribute) {
            self::CREATE => $this->canCreateCredentialDef($token),
            self::LIST => $this->canListCredentialDef($subject, $token),
            self::VIEW => $this->canViewCredentialDef($subject, $token),
            self::EDIT => $this->canEditCredentialDef($subject, $token),
            self::EDIT_ALL => $this->canEditAllCredentialDefs($token),
            self::DELETE => $this->canDeleteCredentialDef($subject, $token),
            default => false,
        };
    }

    private function canCreateCredentialDef(TokenInterface $token): bool
    {
        if ($this->roleChecker->isSuperUser($token)) {
            return true;
        }

        return false;
    }

    private function canListCredentialDef(CredentialDefinition $subject, TokenInterface $token): bool
    {
        return true;
    }

    private function canViewCredentialDef(CredentialDefinition $subject, TokenInterface $token): bool
    {
        // Anyone can view a framework if they know about it
        return true;
    }

    private function canEditCredentialDef(CredentialDefinition $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            // If the user is not logged in then deny access
            return false;
        }

        // Do not allow editing if the user is not an editor
        if (!$this->roleChecker->isEditor($token)) {
            return false;
        }

        // Allow editing if the user is a super-user only
        if ($this->roleChecker->isSuperUser($token)) {
            return true;
        }

        return false;
    }

    private function canDeleteCredentialDef(CredentialDefinition $subject, TokenInterface $token): bool
    {
        return $this->canEditCredentialDef($subject, $token);
    }

    private function canEditAllCredentialDefs(TokenInterface $token): bool
    {
        if ($this->roleChecker->isSuperUser($token)) {
            return true;
        }

        return false;
    }
}
