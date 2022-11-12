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
                    'test' => [
                        'field' => 'test'
                    ]
                ]
            ],
            new RequestInformation('GET', '')
        );

        $this->assertFieldToBe(
            $response,
            'example.test.field',
            FieldType::STRING,
        );

        $this->directive($response, 'example.test.field', Directive::EQUALS, 'test');
        $this->directive($response, 'example.test.field', Directive::CONTAINS, 'te');
        $this->directive($response, 'example.test.field', Directive::NOT_CONTAINS, 'a');
        $this->directive($response, 'example.test.field', Directive::STARTS_WITH, 'te');
        $this->directive($response, 'example.test.field', Directive::ENDS_WITH, 'st');
        $this->directive($response, 'example.test.field', Directive::IS_NOT_EMPTY);




        return $payload;
    }
}