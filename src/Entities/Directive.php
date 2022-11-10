<?php

namespace NovaTech\TestQL\Entities;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Directive
{

    const EQUALS = "directiveEquals";
    const NOT_EQUALS = "directiveNotEquals";
    const GREATER_THAN = "directiveGreaterThan";
    const GREATER_THAN_OR_EQUALS = "directiveGreaterThanOrEqual";
    const LESS_THAN = "directiveLessThan";
    const LESS_THAN_OR_EQUALS = "directiveGreaterThanOrEqual";
    const IN = "directiveIn";
    const IS_NOT_IN = "directiveIsNotIn";
    const IS_NULL = "directiveIsNull";
    const IS_NOT_NULL = "directiveIsNotNull";
    const IS_TRUE = "directiveIsTrue";
    const IS_FALSE = "directiveIsFalse";
    const STARTS_WITH = "directiveStartsWith";
    const NOT_STARTS_WITH = "directiveNotStartsWith";
    const ENDS_WITH = "directiveEndsWith";
    const NOT_ENDS_WITH = "directiveNotEndsWith";
    const CONTAINS = "directiveContains";
    const NOT_CONTAINS = "directiveNotContains";
    const IS_EMPTY = "directiveIsEmpty";
    const IS_NOT_EMPTY = "directiveIsNotEmpty";
    const MATCHES = "directiveMatches";
    const NOT_MATCHES = "directiveNotMatches";
    const CALLBACK = 'directiveCallback';

    public function directiveEquals(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue === $expectedValue;
    }

    public function directiveNotEquals(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue!== $expectedValue;
    }

    public function directiveGreaterThan(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue > $expectedValue;
    }

    public function directiveGreaterThanOrEqual(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue >= $expectedValue;
    }

    public function directiveLessThan(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue < $expectedValue;
    }

    public function directiveLessThanOrEqual(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue <= $expectedValue;
    }

    public function directiveIn(mixed $originalValue, mixed $expectedValue): bool
    {
        return in_array($expectedValue, $originalValue, true);
    }

    public function directiveIsNotIn(mixed $originalValue, mixed $expectedValue): bool
    {
        return!in_array($expectedValue, $originalValue, true);
    }

    public function directiveIsNull(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue === null;
    }

    public function directiveIsNotNull(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue!== null;
    }

    public function directiveIsTrue(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue === true;
    }

    public function directiveIsFalse(mixed $originalValue, mixed $expectedValue) : bool
    {
        return $originalValue === false;
    }

    public function directiveStartsWith(mixed $originalValue, mixed $expectedValue) : bool
    {
        if (is_string($originalValue)) {
            return Str::startsWith($originalValue, $expectedValue);
        }

        if (is_array($originalValue)) {
            return Arr::first($originalValue) === $expectedValue;
        }

        return false;
    }

    public function directiveNotStartsWith(mixed $originalValue, mixed $expectedValue) : bool
    {
        return !$this->directiveStartsWith($originalValue, $expectedValue);
    }

    public function directiveEndsWith(mixed $originalValue, mixed $expectedValue) : bool
    {
        if (is_string($originalValue)) {
            return Str::endsWith($originalValue, $expectedValue);
        }

        if (is_array($originalValue)) {
            return (Arr::last($originalValue)) === $expectedValue;
        }

        return false;
    }

    public function directiveNotEndsWith(mixed $originalValue, mixed $expectedValue) : bool
    {
        return!$this->directiveEndsWith($originalValue, $expectedValue);
    }

    public function directiveContains(mixed $originalValue, mixed $expectedValue) : bool
    {
        if (is_string($originalValue)) {
            return Str::contains($originalValue, $expectedValue);
        }

        if (is_array($originalValue)) {
            return in_array($expectedValue, $originalValue, true) === $expectedValue;
        }

        return false;
    }

    public function directiveNotContains(mixed $originalValue, mixed $expectedValue) : bool
    {
        return!$this->directiveContains($originalValue, $expectedValue);
    }

    public function directiveIsEmpty(mixed $originalValue, mixed $expectedValue) : bool
    {
        return empty($originalValue);
    }

    public function directiveIsNotEmpty(mixed $originalValue, mixed $expectedValue) : bool
    {
        return !$this->directiveIsEmpty($originalValue, $expectedValue);
    }

    public function directiveMatches(mixed $originalValue, mixed $expectedValue) : bool
    {
        $matches = [];
        $status = preg_match($expectedValue, $originalValue, $matches);

        return count($matches) > 0 && $status === 1;
    }

    public function directiveNotMatches(mixed $originalValue, mixed $expectedValue) : bool
    {
        return !$this->directiveMatches($originalValue, $expectedValue);
    }

    public function directiveCallback(mixed $originalValue, callable $expectedValue) : bool
    {
        return (bool) $expectedValue($originalValue);
    }
}