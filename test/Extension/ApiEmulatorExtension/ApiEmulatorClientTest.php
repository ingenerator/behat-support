<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorCapturedRequest;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorCapturedRequestCollection;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorClient;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function get_defined_vars;

class ApiEmulatorClientTest extends TestCase
{

    private string $base_url = 'http://my-emulator:8000';

    private HttpClientInterface $http_client;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ApiEmulatorClient::class, $this->newSubject());
    }

    public function test_it_can_delete_state()
    {
        $this->http_client = new SpyingMockHttpClient(new MockResponse('State purged', ['http_code' => 204]));
        $this->newSubject()->deleteState();
        $this->http_client->assertExactlyOneRequest(
            'DELETE',
            'http://my-emulator:8000/_emulator-meta/global-state',
            [],
        );
    }

    /**
     * @testWith [404, "HTTP 404"]
     *           [202, "HTTP 202"]
     */
    public function test_delete_state_throws_on_unexpected_response($http_code, $expect_msg)
    {
        $this->http_client = new MockHttpClient(new MockResponse('Hmmm', ['http_code' => $http_code]));
        $subject = $this->newSubject();
        $this->expectException(ApiEmulatorException::class);
        $this->expectExceptionMessage($expect_msg);
        $subject->deleteState();
    }

    public function provider_list_requests()
    {
        return [
            'no requests' => [
                '[]',
                new ApiEmulatorCapturedRequestCollection(),
            ],
            'one request' => [
                <<<JSON
                [
                  {
                    "id": "2023-08-24-09-01-21-658835", 
                    "handler_pattern": "/^ping-200/", 
                    "uri": "http://api-emulator-http:9000/ping-200?some=thing", 
                    "method": "DELETE", 
                    "headers": {"Content-Type": ["application/json"]}, 
                    "parsed_body": {"success": true}
                  }
                ]
                JSON,
                new ApiEmulatorCapturedRequestCollection(
                    new ApiEmulatorCapturedRequest(
                        id: "2023-08-24-09-01-21-658835",
                        handler_pattern: "/^ping-200/",
                        method: "DELETE",
                        uri: "http://api-emulator-http:9000/ping-200?some=thing",
                        headers: ["Content-Type" => ["application/json"]],
                        parsed_body: ["success" => true]
                    ),
                ),
            ],
            'two requests' => [
                <<<JSON
                [
                  {
                    "id": "2023-08-24-09-01-21-658835", 
                    "handler_pattern": "**none**", 
                    "uri": "http://api-emulator-http:9000/foo/bar", 
                    "method": "POST", 
                    "headers": {"Content-Type": ["application/json"]}, 
                    "parsed_body": []
                  },
                  {
                    "id": "2023-08-24-09-01-22-658835", 
                    "handler_pattern": "**none**", 
                    "uri": "http://api-emulator-http:9000/p/k", 
                    "method": "GET", 
                    "headers": {"Content-Type": ["text/html"]}, 
                    "parsed_body": []
                  }    
                ]
                JSON,
                new ApiEmulatorCapturedRequestCollection(
                    new ApiEmulatorCapturedRequest(
                        id: "2023-08-24-09-01-21-658835",
                        handler_pattern: "**none**",
                        method: "POST",
                        uri: "http://api-emulator-http:9000/foo/bar",
                        headers: ["Content-Type" => ["application/json"]],
                        parsed_body: []
                    ),
                    new ApiEmulatorCapturedRequest(
                        id: "2023-08-24-09-01-22-658835",
                        handler_pattern: "**none**",
                        method: "GET",
                        uri: "http://api-emulator-http:9000/p/k",
                        headers: ["Content-Type" => ["text/html"]],
                        parsed_body: []
                    ),
                ),
            ],
        ];
    }


    /**
     * @dataProvider provider_list_requests
     */
    public function test_it_can_fetch_list_of_all_requests(
        string $response_body,
        ApiEmulatorCapturedRequestCollection $expect
    ) {
        $this->http_client = new SpyingMockHttpClient(new MockResponse($response_body, ['http_code' => 200]));
        $requests = $this->newSubject()->listRequests();
        $this->http_client->assertExactlyOneRequest(
            'GET',
            'http://my-emulator:8000/_emulator-meta/requests',
            [],
        );
        $this->assertEquals($expect, $requests);
    }

    /**
     * @testWith ["any/old/path"]
     *           ["/any/old/path"]
     */
    public function test_it_can_populate_handler_data_repository($path)
    {
        $this->http_client = new SpyingMockHttpClient(new MockResponse('Stored handler data', ['http_code' => 200]));
        $this->newSubject()->populateRepository($path, ['some' => ['json' => true]]);

        $this->http_client->assertExactlyOneRequest(
            'POST',
            'http://my-emulator:8000/_emulator-meta/handler-data/any/old/path',
            [
                'json' => ['some' => ['json' => true]],
            ]
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->http_client = new MockHttpClient;
    }

    private function newSubject(): ApiEmulatorClient
    {
        return new ApiEmulatorClient(
            $this->http_client,
            $this->base_url
        );
    }
}

class SpyingMockHttpClient extends MockHttpClient
{
    public array $requests = [];

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->requests[] = get_defined_vars();

        return parent::request($method, $url, $options);
    }

    public function assertExactlyOneRequest(string $method, string $url, array $options)
    {
        Assert::assertSame([get_defined_vars()], $this->requests);
    }

}
