<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\TestCase;

class TestResponseFailing extends TestCase
{

    public function test(mixed $payload = null): mixed
    {
        throw new \Exception('test');
    }
}