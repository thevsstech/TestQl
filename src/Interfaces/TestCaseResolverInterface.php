<?php

namespace NovaTech\TestQL\Interfaces;

use NovaTech\TestQL\TestCase;

interface TestCaseResolverInterface
{

    /**
     * @return TestCase[]
     */
    public function getTestCases(): array;
}