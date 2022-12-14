<?php

namespace NovaTech\TestQL;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Entities\Directive;
use NovaTech\TestQL\Entities\FieldType;
use NovaTech\TestQL\Entities\RequestInformation;
use NovaTech\TestQL\Entities\Response;
use NovaTech\TestQL\Exceptions\UnexpectedValueException;
use NovaTech\TestQL\Service\FlattenService;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class TestCase
{

    public ?AuthenticationCapsule $authentication = null;

    abstract public function test(mixed $payload = null): mixed;

    protected ?Response $response = null;
    private array $previousResponses = [];
    private ?SymfonyStyle $symfonyStyle = null;
    private bool $isVerbose = false;
    private ?array $flatten = null;
    private array $parameterMapping = [];
    private array $defaults = [];

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @param array $defaults
     * @return TestCase
     */
    public function setDefaults(array $defaults): static
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @param bool $isVerbose
     */
    public function setIsVerbose(bool $isVerbose): void
    {
        $this->isVerbose = $isVerbose;
    }

    /**
     * @return bool
     */
    public function isVerbose(): bool
    {
        return $this->isVerbose;
    }

    /**
     * @param SymfonyStyle|null $symfonyStyle
     */
    public function setSymfonyStyle(?SymfonyStyle $symfonyStyle): void
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * @return SymfonyStyle|null
     */
    public function getSymfonyStyle(): ?SymfonyStyle
    {
        return $this->symfonyStyle;
    }
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

    private function getFlattenResponse(array $response): array
    {
        if (!$this->flatten) {
            $this->flatten = FlattenService::flatArray($response, '');
        }

        return $this->flatten;
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



    /**
     *
     * @param array $response
     * @param string $field
     * @return array
     */
    private function getAsteriksItems(array $response, string $field) : array{
        $items = [];
        $flattenItems = $this->getFlattenResponse($response);

        // our fields will be given like example.test.field
        // we want to convert it to be example#test#field
        $field = str_replace('.', '#', $field);
        // convert all *'s to (.*?) so we can match them with regex
        // this supports usages like example.*.field
        // or even more advanced stuff like ex*.field.te*
        // this last example will be convered to ex(.*?)#field#te(.*?)
        // which will match the given example.field.test
        $reg = '/^'. str_replace(
            '*',
            '(.*?)',
            $field
        ).'$/m';

        // loop throght all flatten
       foreach ($flattenItems as $key => $value){
           $matches = [];
           preg_match($reg, $key, $matches);


           if (!count($matches)) {
               continue;
           }

           $matchedParameters = array_values(
               array_slice(
                   $matches, 1
               )
           );
           $this->parameterMapping[$matches[0]] = $matchedParameters;
           $items[$matches[0]] = $value;
       }


       return $items;
    }

    /**
     * @throws UnexpectedValueException
     */
    public function getArrKey(array $response, string $field) : array
    {

        // tries to resolve items based on "*"
        // if the field contains "*" character this means we can match
        // multiple values, to do so we will flatten them and match using regex.
        // * means it can match everything
        // todo: Allow custom regex here.
        if (str_contains($field, '*')) {

            $matches =  $this->getAsteriksItems($response, $field);

            $this->outputVerbose(
                sprintf('%d Matches for %s field', count($matches), $field)
            );

            return $matches;
        }

        return [
            Arr::get($response, $field)
        ];
    }

    protected function outputVerbose(string $log): void
    {
        if ($this->isVerbose() && $this->getSymfonyStyle() ) {
        $this->getSymfonyStyle()
            ->writeln($log);
    }

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
        $defaultHeaders = $this->defaults['headers'] ?? [];

        // set default headers
        // note: user defined headers will override the old ones
        $headers = [
            ...$defaultHeaders,
            ...$headers
        ];


        if(!isset($headers['host'])) {
            $headers['host'] =  parse_url($url)['host'] ?? null;
        }


        $statusCode = 0;
        $body = [];
        $this->outputVerbose( sprintf(
            'Request Method: "%s", Uri: "%s", Payload: "%s", Headers: "%s"',
            $method,
            $url,
            json_encode($payload),
            json_encode($headers)
        ));
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