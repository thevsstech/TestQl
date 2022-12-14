<?php

namespace NovaTech\TestQL\Entities;

class Response
{

    public function __construct(
        public readonly int $statusCode,
        public readonly array $response,
        public readonly RequestInformation $requestInformation
    )
    {
    }
}