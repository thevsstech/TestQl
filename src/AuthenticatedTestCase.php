<?php

namespace NovaTech\TestQL;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Entities\Directive;
use NovaTech\TestQL\Entities\FieldType;
use NovaTech\TestQL\Entities\RequestInformation;
use NovaTech\TestQL\Entities\Response;
use NovaTech\TestQL\Exceptions\UnexpectedValueException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class AuthenticatedTestCase extends TestCase
{

    public ?AuthenticationCapsule $authentication = null;

    abstract public function authenticatedTest(?AuthenticationCapsule $authentication, mixed $payload = null): mixed;


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
     *
     * @param mixed|null $payload
     * @return mixed
     */
    public function test(mixed $payload = null): mixed
    {
        return $this->authenticatedTest(
            $this->getAuthentication(),
            $payload
        );
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
     */
    public function request(
        string $method,
        string $url,
        ?array $payload = [],
        ?array $headers = [],
        ?array $options = []
    ): Response
    {
        $headers = $this->mapHeaders($headers);


        if ($this->getAuthentication() && $this->getAuthentication()->type !== AuthenticationCapsule::NO_AUTHENTICATION && !isset($headers['authorization'])) {
            $headers['authorization'] = sprintf('%s %s', mb_convert_case($this->getAuthentication()->type, MB_CASE_TITLE), $this->getAuthentication()->token);
        }


        return parent::request($method, $url, $payload, $headers, $options);
    }
}