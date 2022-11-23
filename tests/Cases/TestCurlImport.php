<?php

namespace NovaTech\Tests\Cases;



use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\AuthenticatedTestCase;

use NovaTech\TestQL\TestCase;

final class TestCurlImport extends AuthenticatedTestCase {

             public function authenticatedTest(?AuthenticationCapsule $authentication, mixed $payload = null): mixed{
      
             $headers = array (
  'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:108.0) Gecko/20100101 Firefox/108.0',
  'Accept' => 'application/json',
  'Accept-Language' => 'tr-TR,tr;q=0.8,en-US;q=0.5,en;q=0.3',
  'Accept-Encoding' => 'gzip, deflate, br',
  'Content-Type' => 'application/json',
  'Connection' => 'keep-alive',
  'Sec-Fetch-Dest' => 'empty',
  'Sec-Fetch-Mode' => 'cors',
  'Sec-Fetch-Site' => 'same-site',
  'Sec-GPC' => '1',
  'TE' => 'trailers',
);
             $json = array (
                 'data' => 'test'
             );
             $options = [
                 'headers' => $headers,
                 'json' => $json
            ];
             $response = $this->request("POST", "http://localhost/board", $options);
             $this->assertResponseSuccessful($response, true);

             return $payload;
      }

      }