<?php

namespace NovaTech\TestQL\Interfaces;

use NovaTech\TestQL\Entities\AuthenticationCapsule;

interface AuthenticationResolverInterface
{

    /**
     * @return AuthenticationCapsule|AuthenticationCapsule[]
     */
    public function authenticate(array|AuthenticationCapsule|null $authenticationCapsule = null): array|AuthenticationCapsule;
}