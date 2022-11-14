<?php


use NovaTech\TestQL\Resolvers\ArrayTestResolver;
use NovaTech\Tests\Cases\TestApiResolvesDashboard;
use NovaTech\Tests\Cases\TestAsteriks;
use NovaTech\Tests\Cases\TestAuthenticationResolver;
use NovaTech\Tests\Cases\TestClassUsesPersistentAuth;
use NovaTech\Tests\Cases\TestDependency;
use NovaTech\Tests\Cases\TestDirectives;
use NovaTech\Tests\Cases\TestLocalhost;
use NovaTech\Tests\Cases\TestResponseFailing;
use NovaTech\Tests\Cases\TestSimpleRequest;
use NovaTech\Tests\Cases\TestSimpleResponse;
use NovaTech\Tests\Cases\TestSimpleResponseWithStatusCode;
use NovaTech\Tests\Cases\TestWithNoDependency;

$resolver = new ArrayTestResolver([
    new TestAuthenticationResolver(),
    new TestSimpleResponseWithStatusCode(),
    new TestSimpleResponse(),
    new TestSimpleRequest(),
    new TestDependency(),
    new TestLocalhost(),
    new TestResponseFailing(),
    new TestClassUsesPersistentAuth(),
    new TestAsteriks(),
    new TestDirectives(),
    new TestApiResolvesDashboard(),
    new TestWithNoDependency()
]);


return $resolver;