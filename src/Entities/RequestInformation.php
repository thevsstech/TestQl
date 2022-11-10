<?php

namespace NovaTech\TestQL\Entities;

class RequestInformation
{

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $headers = [],
        public readonly array $payload = []
    )
    {
    }
}