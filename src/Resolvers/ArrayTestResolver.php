<?php

namespace NovaTech\TestQL\Resolvers;

use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;

class ArrayTestResolver implements TestCaseResolverInterface
{
    public function __construct(public iterable $items){}

    public function getTestCases(): array
    {
        return [...$this->items];
    }
}