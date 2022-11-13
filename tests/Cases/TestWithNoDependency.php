<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\TestCase;

class TestWithNoDependency extends TestCase
{

    public function test(mixed $payload = null): mixed
    {
        return $payload;
    }
}