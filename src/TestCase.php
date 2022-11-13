<?php

namespace NovaTech\TestQL;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Entities\Directive;
use NovaTech\TestQL\Entities\FieldType;
use NovaTech\TestQL\Entities\RequestInformation;
use NovaTech\TestQL\Entities\Response;
use NovaTech\TestQL\Exceptions\UnexpectedValueException;

abstract class TestCase
{

    public ?AuthenticationCapsule $authentication = null;

    abstract public function test(mixed $payload = null): mixed;

    protected ?Response $response = null;
    private array $previousResponses = [];

    /**
     * @param array $previousResponses
     */
    public function setPreviousResponses(array $previousResponses): void
    {
        $this->previousResponses = $previousResponses;
    }

    public function getPreviousResponses(): array
    {
        return $this->previousResponses;
    }

    /**
     * @return AuthenticationCapsule|null
     */
    public function getAuthentication(): ?AuthenticationCapsule
    {
        return $this->authentication;
    }

    /**
     * @param AuthenticationCapsule|null $authentication
     */
    public function setAuthentication(?AuthenticationCapsule $authentication): static
    {
        $this->authentication = $authentication;
        return $this;
    }


    /**
     * converts headers keys to lowercase
     *
     * @param array $headers
     * @return array
     */
    private function mapHeaders(array $headers): array
    {
        $newHeaders = [];
        foreach ($headers as $key => $value) {
            $newHeaders[mb_convert_case($key, MB_CASE_LOWER)] = $value;
        }

        return $newHeaders;
    }

    /**
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function assertResponseFails(Response $response): static
    {
        $status = $response->statusCode;

        if ($status >= 200 && $status < 300) {
            throw new UnexpectedValueException(
                sprintf('Response meant to fail but its successful')
            );
        }

        return $this;
    }

    /**
     * @param Response $response
     * @return $this
     * @throws UnexpectedValueException
     */
    public function assertResponseSuccessful(Response $response, bool $strict = true): static
    {
        $status = $response->statusCode;

        if ($status >= 200 && $status < 300) {


            $method = $response->requestInformation->method;

            if ($strict) {
                if ($method === 'DELETE' && ($status !== 204 && $status !== 202)) {
                    throw new UnexpectedValueException(
                        sprintf('When strict mode is enabled, all delete operations must either return 202 or 204, %d returned', $status)
                    );
                }

                if ($method === 'POST' && ($status !== 200 && $status !== 202)) {
                    throw new UnexpectedValueException(
                        sprintf('When strict mode is enabled, all post operations must either return 202 or 200, %d returned', $status)
                    );
                }

                if ($method === 'PUT' && ($status !== 201 && $status !== 202)) {
                    throw new UnexpectedValueException(
                        sprintf('When strict mode is enabled, all put operations must either return 201 or 202, %d returned', $status)
                    );
                }

                if ($method === 'GET' && $status === 200) {
                    throw  new UnexpectedValueException(
                        sprintf('When strict mode is enabled, all get operations must return 200, %d returned', $status)
                    );
                }
            }
            return $this;
        }

        throw new UnexpectedValueException(
            sprintf(
                'Expected status code between %d and %d, got %d.',
                200,
                300,
                $status)
        );
    }

    /**
     * @throws UnexpectedValueException
     */
    public function directive(
        Response      $response,
        string        $field,
        ?string       $directiveType,
        mixed         $value = null
    ): static
    {

        $items = $this->getArrKey($response->response, $field);

        if (!is_array($items)) {
            $items = [$items];
        }


        $throwException = fn(mixed $originalValue) => throw new UnexpectedValueException(
            sprintf(
                'Field "%s" does not follows directives as %s with params %s, value: %s',
                $field,
                $directiveType,
                json_encode($value),
                json_encode($originalValue)
            )
        );

        $directive = new Directive();


        foreach ($items as $item) {
           $result = $directive->{$directiveType}($item, $value);

            if (!$result) {
                $throwException($item);
            }
        }

        return $this;
    }

    /**
     * @throws UnexpectedValueException
     */
    public function assertFieldToBe(
        Response   $response,
        string     $field,
        FieldType  $expectedFieldType,
    ): static
    {
        $items = $this->getArrKey($response->response, $field);

        if (!is_array($items)) {
            $items = [$items];
        }

        $throwException = fn(string $expected, string $got) => throw new UnexpectedValueException(
            sprintf(
                'Expected field "%s" to be of type "%s", got "%s"',
                $field,
                $expected,
                $got
            )
        );

        $testAgainst = [
            // since we are api testing and we are using php, we might get result either
            // an object or an array
            'object' => fn($value ) => is_object($value) || (is_array($value) && !array_is_list($value)),
            'array' => fn($value ) => is_array($value) && array_is_list($value),
            'boolean' => FieldType::BOOLEAN,
            'integer' => FieldType::INTEGER,
            'float' => FieldType::FLOAT,
            'string' => FieldType::STRING,
            'date' => FieldType::DATE,
            'null' => FieldType::NULL,
        ];

        foreach ($items as $item) {
            $itemsType = gettype($item);
            $checker = $testAgainst[$itemsType];


            // lets check if any one of the fields is callable
            // we will call the function and check if its true
            if ($checker instanceof \Closure && !$checker($item)) {
                $throwException($expectedFieldType, $itemsType);
            } else if ($checker !== $expectedFieldType){
                $throwException($expectedFieldType->name, $itemsType);
            }
        }

        return $this;
    }



    /**
     * @param array $response
     * @param string $field
     * @param mixed $expected
     * @return $this
     * @throws UnexpectedValueException
     */
    public function assertFieldEquals(Response $response, string $field, mixed $expected): static
    {
        $value = Arr::get($response->response, $field, null);

        if ($value !== $expected) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected field "%s" to be "%s", got "%s"',
                    $field,
                    $expected,
                    json_encode($value)
                )
            );
        }

        return $this;
    }

    public function getFilteredTests(array $tests, array $groups)
    {

        if (empty($groups)) {
            return $tests;
        }

        $filteredTests = [];
        foreach ($tests as $test) {
            foreach ($groups as $group) {
                $testClass = get_class($test);
                if (isset($this->groupsFixtureMapping[$group][$testClass])) {
                    $filteredTests[$testClass] = $test;
                    continue 2;
                }
            }
        }

        return $filteredTests;
    }
    /**
     * @throws UnexpectedValueException
     */
    public function getArrKey(array $response, string $field)
    {
        if (!str_contains($field, '.*')) {
            return Arr::get($response, $field, null);
        }

        $split = explode('.*', $field);
        if (count($split) > 2) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected field "%s" cant have more than one asterisk fields"',
                    $field
                )
            );
        }


        $returns = [];


        $startPathItems = Arr::get($response, $split[0]);

        foreach ($startPathItems as $item) {
            $rest = $split[1];
            $posOfDot = strpos($rest, '.', 0);

            if ($posOfDot !== false) {

                $rest = Str::after($rest, '.');
            }

            $returns[] = Arr::get($item, $rest, null);
        }

        return $returns;

    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $payload
     * @param array|null $headers
     * @param array|null $options
     * @return Response
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws GuzzleException
     */
    public function request(
        string $method,
        string $url,
        ?array $payload = null,
        ?array $headers = [],
        ?array $options = []
    ): Response
    {
        $client = new Client($options);


        if (!isset($headers['accept'])) {
            $headers['accept'] = 'application/json';
        }

        if(!isset($headers['host'])) {
            $headers['host'] =  parse_url($url)['host'] ?? null;
        }

        if (is_array($payload)) {
            $headers['Content-Type'] = 'application/json';
        }

        $statusCode = 0;
        $body = [];

        try {
            $request = new Request(
                $method,
                $url,
                $headers,
                $payload ? json_encode($payload) : null
            );
            $response = $client->sendAsync($request)->wait();
            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);
        }catch(RequestException $requestException){
            $response = $requestException->getResponse();

            if (!$response) {
                $statusCode = 500;
            }else{
                $body = (string) $response->getBody();
                $statusCode = $response->getStatusCode();
                $body = json_decode($body, true);
            }
        }

        $requestInformation = new RequestInformation(
            $method,
            $url,
            $headers,
            $payload
        );

        $response = new Response(
            $statusCode,
            $body ?? [],
            $requestInformation
        );


        $this->response = $response;
        return $response;
    }


}