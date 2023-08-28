<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Gherkin\Node\PyStringNode;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorAssertionFailedException;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorCapturedRequest;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorCapturedRequestCollection;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorClient;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\SimpleApiEmulatorContext;
use PHPUnit\Framework\TestCase;

class SimpleApiEmulatorContextTest extends TestCase
{
    private ApiEmulatorClient $client;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(SimpleApiEmulatorContext::class, $this->newSubject());
    }

    public function provider_no_requests()
    {
        return [
            'passes with no requests' => [
                [],
                false,
            ],
            'fails with one request' => [
                [ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator/anything')],
                "Expected no API emulator requests but got:\n - POST http://emulator/anything",
            ],
        ];
    }


    /**
     * @dataProvider provider_no_requests
     */
    public function test_it_can_assert_no_requests(array $scenario_requests, false|string $expect_throws)
    {
        $this->client = $this->stubClientWithScenarioRequests($scenario_requests);
        $subject = $this->newSubject();

        $this->testAssertionMethod(fn () => $subject->assertNoRequests(), $expect_throws);
    }


    public function provider_request_with_body()
    {
        return [
            'fails with no request' => [
                [],
                'Expected exactly one request to POST http://emulator/something?f=b but the emulator did not capture any requests.',
            ],
            'fails with request to different url' => [
                [
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'POST',
                        uri: 'http://emulator/uhoh',
                        parsed_body: ['some' => ['nested' => 'data']]
                    ),
                ],
                "Expected exactly one request to POST http://emulator/something?f=b but got:\n - POST http://emulator/uhoh",
            ],
            'fails with request having different method' => [
                [
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'GET',
                        uri: 'http://emulator/something?f=b',
                        parsed_body: ['some' => ['nested' => 'data']]
                    ),
                ],
                "Expected exactly one request to POST http://emulator/something?f=b but got:\n - GET http://emulator/something?f=b",
            ],
            'fails with multiple matching requests' => [
                [
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'POST',
                        uri: 'http://emulator/something?f=b',
                        parsed_body: ['some' => ['nested' => 'data']]
                    ),
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'POST',
                        uri: 'http://emulator/something?f=b',
                        parsed_body: ['some' => ['nested' => 'data']]
                    ),

                ],
                "Expected exactly one request to POST http://emulator/something?f=b but got:\n - POST http://emulator/something?f=b\n - POST http://emulator/something?f=b",
            ],
            'fails with incorrect body' => [
                [
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'POST',
                        uri: 'http://emulator/something?f=b',
                        parsed_body: ['some' => ['noosted' => 'data']]
                    ),
                ],
                <<<TEXT
                    Payload of POST to http://emulator/something?f=b did not match expectation:
                    --- Expected
                    +++ Actual
                    @@ @@
                     Array &0 (
                         'some' => Array &1 (
                    -        'nested' => 'data'
                    +        'noosted' => 'data'
                         )
                     )
                    TEXT,
            ],
            'passes with correct request and body' => [
                [
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'POST',
                        uri: 'http://emulator/something?f=b',
                        parsed_body: ['some' => ['nested' => 'data']]
                    ),
                ],
                false,
            ],
            'ignores requests to other urls or methods when passing' => [
                [
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'GET',
                        uri: 'http://emulator/other-thing',
                        parsed_body: [],
                    ),
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'POST',
                        uri: 'http://emulator/other-thing',
                        parsed_body: ['some' => ['nested' => 'data']]
                    ),
                    ApiEmulatorCapturedRequest::stubWith(
                        method: 'POST',
                        uri: 'http://emulator/something?f=b',
                        parsed_body: ['some' => ['nested' => 'data']]
                    ),
                ],
                false,
            ],
        ];
    }

    /**
     * @dataProvider provider_request_with_body
     */
    public function test_it_can_assert_single_exact_request_with_body(
        array $scenario_requests,
        false|string $expect_throws
    ) {
        $this->client = $this->stubClientWithScenarioRequests($scenario_requests);
        $subject = $this->newSubject();
        $expected_body = new PyStringNode(['{"some": {"nested": "data"}}'], 15);
        $this->testAssertionMethod(
            fn () => $subject->assertCapturedOneRequestWithBody(
                'POST',
                'http://emulator/something?f=b',
                $expected_body
            ),
            $expect_throws
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->stubClientWithScenarioRequests([]);
    }

    private function newSubject(): SimpleApiEmulatorContext
    {
        $subject = new SimpleApiEmulatorContext;
        $subject->setApiEmulator($this->client);

        return $subject;
    }

    private function stubClientWithScenarioRequests(array $scenario_requests)
    {
        return new class($scenario_requests) extends ApiEmulatorClient {
            public function __construct(private readonly array $stubbed_requests)
            {
            }

            public function listRequests(): ApiEmulatorCapturedRequestCollection
            {
                return new ApiEmulatorCapturedRequestCollection(...$this->stubbed_requests);
            }
        };
    }

    private function testAssertionMethod(\Closure $callable, bool|string $expect_throws): void
    {
        try {
            $callable();
            $this->assertFalse($expect_throws, 'Nothing was thrown');
        } catch (ApiEmulatorAssertionFailedException $e) {
            $this->assertSame($expect_throws, $e->getMessage());
        }
    }

}
