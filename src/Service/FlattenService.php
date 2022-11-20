<?php

namespace NovaTech\TestQL\Service;

/**
 * This service ment to be used
 */
class FlattenService
{

    public static function flatArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $newPrefixKey = $prefix !== '' ? $prefix .'#' . $key : $key;
                $result = [
                    ...$result,
                    ...static::flatArray($value, $newPrefixKey)
                ];
            } else {
                $result[$prefix . '#' . $key] = $value;
            }
        }

        return $result;
    }

}