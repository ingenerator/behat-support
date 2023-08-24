<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorAssertionFailedException;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorCapturedRequest;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorCapturedRequestCollection;
use PHPUnit\Framework\TestCase;

class ApiEmulatorCapturedRequestCollectionTest extends TestCase
{

    public function provider_assert_empty()
    {
        return [
            'is empty' => [
                new ApiEmulatorCapturedRequestCollection(),
                false,
            ],
            'with request' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'DELETE', uri: 'http://emulator:90/foobar'),
                ),
                <<<TEXT
                Expected no API emulator requests but got:
                 - DELETE http://emulator:90/foobar
                TEXT
                ,
            ],
        ];
    }

    /**
     * @dataProvider provider_assert_empty
     */
    public function test_it_can_assert_that_it_is_empty(
        ApiEmulatorCapturedRequestCollection $subject,
        false|string $expect_exception
    ) {
        $this->testAssertionMethod(fn () => $subject->assertEmpty(), $expect_exception);
    }

    public function provider_assert_single_request()
    {
        return [
            'no requests' => [
                new ApiEmulatorCapturedRequestCollection(),
                'http://emulator:90/foo/bar',
                'Expected exactly one request to GET http://emulator:90/foo/bar but the emulator did not capture any requests.',
            ],
            'request to wrong URL' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/foo/baz'),
                ),
                'http://emulator:90/foo/bar',
                <<<TEXT
                Expected exactly one request to GET http://emulator:90/foo/bar but got:
                 - POST http://emulator:90/foo/baz
                TEXT,
            ],
            'request with wrong method' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/foo/bar'),
                ),
                'http://emulator:90/foo/bar',
                <<<TEXT
                Expected exactly one request to GET http://emulator:90/foo/bar but got:
                 - POST http://emulator:90/foo/bar
                TEXT,
            ],
            'multiple requests to expected route' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/foo/bar'),
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/foo/bar'),
                ),
                'http://emulator:90/foo/bar',
                <<<TEXT
                Expected exactly one request to GET http://emulator:90/foo/bar but got:
                 - GET http://emulator:90/foo/bar
                 - GET http://emulator:90/foo/bar
                TEXT,
            ],
            'passes with correct method & URL' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/foo/bar'),
                ),
                'http://emulator:90/foo/bar',
                false,
            ],
            'passes even if there are requests to other URLS' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/foo/bar'),
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/something-else'),
                ),
                'http://emulator:90/foo/bar',
                false,
            ],
            'passes even if there are requests with another method' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/foo/bar'),
                    ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/foo/bar'),
                ),
                'http://emulator:90/foo/bar',
                false,
            ],
            'fails with different querystring' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/foo/bar?some=value'),
                ),
                'http://emulator:90/foo/bar?other=value',
                <<<TEXT
                Expected exactly one request to GET http://emulator:90/foo/bar?other=value but got:
                 - GET http://emulator:90/foo/bar?some=value
                TEXT,
            ],
            'passes with correct querystring' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/foo/bar?some=value'),
                ),
                'http://emulator:90/foo/bar?some=value',
                false,
            ],
        ];
    }

    /**
     * @dataProvider provider_assert_single_request
     */
    public function test_it_can_assert_it_has_a_single_request_to_url(
        ApiEmulatorCapturedRequestCollection $subject,
        string $search_uri,
        false|string $expect_exception
    ) {
        $this->testAssertionMethod(
            fn () => $subject->assertSingleRequestTo('GET', $search_uri),
            $expect_exception
        );
    }

    public function test_assert_single_returns_the_matched_request()
    {
        $request = ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/something?here');
        $subject = new ApiEmulatorCapturedRequestCollection(
            ApiEmulatorCapturedRequest::stubWith(uri: 'http://emulator:90/otherstuff'),
            $request,
            ApiEmulatorCapturedRequest::stubWith(uri: 'http://emulator:90/different'),
        );

        $this->assertSame(
            $request,
            $subject->assertSingleRequestTo('POST', 'http://emulator:90/something?here')
        );
    }

    private function testAssertionMethod(\Closure $callable, bool|string $expect_exception): void
    {
        try {
            $callable();
            $this->assertFalse($expect_exception, 'No exception was thrown');
        } catch (ApiEmulatorAssertionFailedException $e) {
            $this->assertSame($expect_exception, $e->getMessage(), 'Expected exception was thrown');
        }
    }
}
