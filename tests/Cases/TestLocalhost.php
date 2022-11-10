<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\TestCase;

class TestLocalhost extends  TestCase
{

    public function test(mixed $payload = null): mixed
    {

        $response = $this->request('GET', 'http://localhost:8000/api/v1', headers: [

        ]);

        $this->assertResponseFails($response);

        return $payload;
    }
}