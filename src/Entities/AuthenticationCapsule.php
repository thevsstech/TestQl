<?php

namespace NovaTech\TestQL\Entities;

class AuthenticationCapsule
{

    const NO_AUTHENTICATION = 'none';
    const BEARER_AUTHENTICATION = 'Bearer';
    const TOKEN_AUTHENTICATION = 'Token';
    const BASIC_AUTHENTICATION = 'Basic';

    public function __construct(
        public readonly string $type,
        public readonly string $token,
        public readonly mixed $identifier = null,
    )
    {
    }
}