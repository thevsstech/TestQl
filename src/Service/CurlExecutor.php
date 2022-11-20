<?php

namespace NovaTech\TestQL\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use NovaTech\TestQL\Exceptions\CurlExecutionException;

class CurlExecutor
{
    public function __construct(
        private readonly CurlParser $parser,
        private readonly string $curl
    )
    {
    }


    /**
     * @throws GuzzleException
     * @throws CurlExecutionException
     */
    public function execute()
    {
        $headers = $this->parser->getHeaders();
        $url = (string) $this->parser->getUri();
        $body = (string) $this->parser->getBody();


        $client = new Client();
      try{
          $response = $client->request(
              $this->parser->getMethod(), $url, [
              'body' => $body,
              'headers' => $headers
          ]);

          return json_decode(
              (string) $response->getBody(),
              true
          );
      }catch(\Exception $e){
          throw new CurlExecutionException(
              $e->getMessage(),
              $e->getCode(),
              $e->getPrevious()
          );
      }
    }
}