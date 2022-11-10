<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Interfaces\TestDependsOnInterface;
use NovaTech\TestQL\TestCase;

class TestSimpleResponseWithStatusCode extends TestCase implements TestDependsOnInterface
{


    public function dependsOn(): array
    {
        return [
            TestSimpleRequest::class,
            TestSimpleResponse::class
        ];
    }

    public function test(mixed $payload = null): mixed
    {
        usleep(2000);


        return $payload;
    }
}