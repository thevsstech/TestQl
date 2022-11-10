<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Interfaces\TestDependsOnInterface;
use NovaTech\TestQL\TestCase;

class TestSimpleResponse extends TestCase implements TestDependsOnInterface
{

    public function test(mixed $payload = null): mixed
    {
        usleep(2000);

        return $payload;

    }

    public function dependsOn(): array
    {
        return [
            TestSimpleRequest::class
        ];
    }
}