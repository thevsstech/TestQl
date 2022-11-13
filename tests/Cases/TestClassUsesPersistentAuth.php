<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Interfaces\AuthenticationResolverInterface;
use NovaTech\TestQL\Interfaces\TestDependsOnInterface;
use NovaTech\TestQL\TestCase;

class TestClassUsesPersistentAuth extends TestCase implements TestDependsOnInterface, AuthenticationResolverInterface
{

    public function test(mixed $payload = null): mixed
    {


         return $payload;
    }

    public function dependsOn(): array
    {
        return [TestAuthenticationResolver::class];
    }

    public function authenticate(AuthenticationCapsule|array|null $authenticationCapsule = null): array|AuthenticationCapsule
    {
        if (!$authenticationCapsule || $authenticationCapsule?->token !== 'test') {
            throw new \UnexpectedValueException('Wrong authentication');
        }

        return $authenticationCapsule;
    }
}