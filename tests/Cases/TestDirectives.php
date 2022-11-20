<?php

namespace NovaTech\Tests\Cases;

use NovaTech\TestQL\Entities\Directive;
use NovaTech\TestQL\Entities\FieldType;
use NovaTech\TestQL\Entities\RequestInformation;
use NovaTech\TestQL\Entities\Response;
use NovaTech\TestQL\Exceptions\UnexpectedValueException;
use NovaTech\TestQL\TestCase;

class TestDirectives extends TestCase
{

    /**
     * @throws UnexpectedValueException
     */
    public function test(mixed $payload = null): mixed
    {
        $response = new Response(
            200,
            [
                'example' => [
                    'a' => 'b',
                    'c' => 'd',
                    'e' => 'f',
                    'test' => [
                        'field' => 'test',
                        'asd' => 'asd',
                        'aaa' => 'b'
                    ],
                    3 => 4
                ],
                [
                    'asdad' => 'asdasd',
                    1 => 'asdds'
                ]
            ],
            new RequestInformation('GET', '')
        );

        $this->assertFieldToBe(
            $response,
            '*.*.field',
            FieldType::STRING,
        );

        $this->directive($response, 'example.*.field', Directive::EQUALS, 'test');
        $this->directive($response, 'example.test.field', Directive::CONTAINS, 'te');
        $this->directive($response, '*.test.field', Directive::NOT_CONTAINS, 'a');
        $this->directive($response, 'example.test.*', Directive::STARTS_WITH, 'te');
        $this->directive($response, 'example.*.field', Directive::ENDS_WITH, 'st');
        $this->directive($response, '*.test.*', Directive::IS_NOT_EMPTY);




        return $payload;
    }
}