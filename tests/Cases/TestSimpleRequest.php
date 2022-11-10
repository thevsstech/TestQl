<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\TestCase;

class TestSimpleRequest extends TestCase
{

    public function test(mixed $payload = null): mixed
    {
        usleep(2000);

        return $payload;

    }
}