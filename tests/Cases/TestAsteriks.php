<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Exceptions\UnexpectedValueException;
use NovaTech\TestQL\TestCase;

class TestAsteriks extends TestCase
{

    /**
     * @throws UnexpectedValueException
     */
    public function test(mixed $payload = null): mixed
    {

        $data = [
            'items' => [
                [
                    'field' => 'asd'
                ],

                ['field' => 'username']
            ]
        ];


        var_dump($this->getArrKey($data, 'items.*.field'));

        return null;
    }
}