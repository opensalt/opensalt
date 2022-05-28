<?php

namespace App\Request\ParamConverter;

use App\Entity\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class UserParamConverter implements ParamConverterInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $name = $configuration->getName();

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $request->attributes->set($name, $user);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return User::class === $configuration->getClass() && 'user' === $configuration->getName();
    }
}
