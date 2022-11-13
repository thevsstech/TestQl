<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\AuthenticatedTestCase;
use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Interfaces\TestDependsOnInterface;

class TestApiResolvesDashboard extends AuthenticatedTestCase implements TestDependsOnInterface
{

    public function authenticatedTest(?AuthenticationCapsule $authentication, mixed $payload = null): mixed
    {

        $response = $this->request('GET', $_ENV['TEST_AUTH_URL']. $_ENV['TEST_DASHBOARD_URL']);



        return $payload;
    }

    public function dependsOn(): array
    {
        return [
            TestAuthenticationResolver::class
        ];
    }
}