<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Interfaces\AuthenticationResolverInterface;
use NovaTech\TestQL\Interfaces\PersistentAuthenticationInterface;
use NovaTech\TestQL\TestCase;

class TestPersistenAuth extends TestCase implements AuthenticationResolverInterface,PersistentAuthenticationInterface
{

    public function test(mixed $payload = null): mixed
    {


         return null;
    }

    public function authenticate(AuthenticationCapsule|array|null $authenticationCapsule = null): array|AuthenticationCapsule
    {
        return new AuthenticationCapsule(
            AuthenticationCapsule::BEARER_AUTHENTICATION,
            'test'
        );
    }
}