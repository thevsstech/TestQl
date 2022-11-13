<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Interfaces\AuthenticationResolverInterface;
use NovaTech\TestQL\Interfaces\PersistentAuthenticationInterface;
use NovaTech\TestQL\TestCase;

class TestAuthenticationResolver extends TestCase implements AuthenticationResolverInterface, PersistentAuthenticationInterface
{


    public function authenticate(AuthenticationCapsule|array|null $authenticationCapsule = null): array|AuthenticationCapsule
    {
        $auths = [
            [
                'email' => $_ENV['TEST_AUTH_EMAIL'],
                'password' => $_ENV['TEST_AUTH_PW'],
            ]
        ];
        $capsules  = [];

        foreach ($auths as $auth){
            $response = $this->request('POST', $_ENV['TEST_AUTH_URL'].'/auth/login', $auth);
            $capsules[]= new AuthenticationCapsule('bearer', $response->response['token'], $_ENV['TEST_AUTH_EMAIL']);

        }

        return $capsules;
    }

    public function test(mixed $payload = null): mixed
    {
        return $payload;
    }
}