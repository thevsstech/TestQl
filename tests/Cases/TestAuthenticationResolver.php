<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Interfaces\AuthenticationResolverInterface;
use NovaTech\TestQL\TestCase;

class TestAuthenticationResolver extends TestCase implements AuthenticationResolverInterface
{


    public function authenticate(AuthenticationCapsule|array|null $authenticationCapsule = null): array|AuthenticationCapsule
    {

    }

    public function test(mixed $payload = null): mixed
    {

    }
}