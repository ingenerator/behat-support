<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Error;
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

    public function provider_filter_by_url()
    {
        $match_rq_1 = ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/some?url=here');
        $match_rq_2 = ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/some?url=here');
        $match_rq_3 = ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/some?url=here');
        $match_rq_post = ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/some?url=here');


        return [
            'works with empty collection' => [
                new ApiEmulatorCapturedRequestCollection(),
                'http://emulator:90/whatever',
                'GET',
                new ApiEmulatorCapturedRequestCollection(),
            ],
            'returns empty with no match on URI' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/anything'),
                ),
                'http://emulator:90/whatever',
                'GET',
                new ApiEmulatorCapturedRequestCollection(),
            ],
            'returns empty with no match on URI and method' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/whatever'),
                ),
                'http://emulator:90/whatever',
                'GET',
                new ApiEmulatorCapturedRequestCollection(),
            ],
            'returns filtered by URI and method' => [
                new ApiEmulatorCapturedRequestCollection(
                    $match_rq_1,
                    ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/other'),
                    $match_rq_2,
                    $match_rq_post,
                    $match_rq_3

                ),
                'http://emulator:90/some?url=here',
                'GET',
                new ApiEmulatorCapturedRequestCollection($match_rq_1, $match_rq_2, $match_rq_3),
            ],
            'returns filtered by URI with any method' => [
                new ApiEmulatorCapturedRequestCollection(
                    $match_rq_1,
                    ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/other'),
                    $match_rq_2,
                    $match_rq_post,
                    $match_rq_3

                ),
                'http://emulator:90/some?url=here',
                null,
                new ApiEmulatorCapturedRequestCollection($match_rq_1, $match_rq_2, $match_rq_post, $match_rq_3),
            ],
        ];
    }

    /**
     * @dataProvider provider_filter_by_url
     */
    public function test_can_return_collection_filtered_by_url_and_optionally_method(
        ApiEmulatorCapturedRequestCollection $subject,
        string $uri,
        ?string $method,
        ApiEmulatorCapturedRequestCollection $expect
    ) {
        $original = clone($subject);
        if ($method === null) {
            $filtered = $subject->filterByUri($uri);
        } else {
            $filtered = $subject->filterByUriAndMethod($uri, $method);
        }

        $this->assertEquals($expect, $filtered, 'Filtered collection matches expectation');
        $this->assertEquals($original, $subject, 'Filtering does not modify source collection');
    }

    public function test_can_filter_by_arbitrary_callback()
    {
        $rq1 = ApiEmulatorCapturedRequest::stubWith(headers: ['content-type' => ['application/json']]);
        $rq2 = ApiEmulatorCapturedRequest::stubWith(headers: ['content-type' => ['application/json']]);
        $subject = new ApiEmulatorCapturedRequestCollection(
            $rq1,
            ApiEmulatorCapturedRequest::stubWith(headers: []),
            $rq2
        );
        $original = clone($subject);

        $filtered = $subject->filter(
            fn (ApiEmulatorCapturedRequest $rq) => ['application/json'] === ($rq->headers['content-type'] ?? null)
        );
        $this->assertEquals(
            new ApiEmulatorCapturedRequestCollection($rq1, $rq2),
            $filtered,
            'Filtered collection matches expectation'
        );
        $this->assertEquals($original, $subject, 'Filtering does not modify source collection');
    }

    public function provider_count()
    {
        return [
            'empty' => [
                new ApiEmulatorCapturedRequestCollection,
                0,
            ],
            'one' => [
                new ApiEmulatorCapturedRequestCollection(ApiEmulatorCapturedRequest::stubWith()),
                1,
            ],
            'more' => [
                new ApiEmulatorCapturedRequestCollection(
                    ApiEmulatorCapturedRequest::stubWith(),
                    ApiEmulatorCapturedRequest::stubWith(),
                    ApiEmulatorCapturedRequest::stubWith(),
                ),
                3,
            ],
        ];
    }

    /**
     * @dataProvider provider_count
     */
    public function test_it_provides_count_of_requests(ApiEmulatorCapturedRequestCollection $subject, int $expect)
    {
        $this->assertSame($expect, count($subject), 'Countable natively');
    }

    public function test_its_request_list_cannot_be_modified()
    {
        $subject = new ApiEmulatorCapturedRequestCollection();
        $this->expectException(Error::class);
        $this->expectExceptionMessage('readonly');
        $subject->requests[] = ApiEmulatorCapturedRequest::stubWith();
    }

    public function provider_nth_request()
    {
        $rq1 = ApiEmulatorCapturedRequest::stubWith();
        $rq2 = ApiEmulatorCapturedRequest::stubWith();
        $rq3 = ApiEmulatorCapturedRequest::stubWith();

        return [
            'first' => [
                new ApiEmulatorCapturedRequestCollection($rq1, $rq2, $rq3),
                1,
                $rq1,
            ],
            'third' => [
                new ApiEmulatorCapturedRequestCollection($rq1, $rq2, $rq3),
                3,
                $rq3,
            ],
        ];
    }

    /**
     * @dataProvider provider_nth_request
     */
    public function test_it_can_provide_nth_request(ApiEmulatorCapturedRequestCollection $subject, int $n, $expect)
    {
        $this->assertSame($expect, $subject->nthRequest($n));
    }

    public function test_it_throws_for_nth_request_that_does_not_exist()
    {
        $subject = new ApiEmulatorCapturedRequestCollection(
            ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/a-get'),
            ApiEmulatorCapturedRequest::stubWith(method: 'POST', uri: 'http://emulator:90/b-get'),
            ApiEmulatorCapturedRequest::stubWith(method: 'GET', uri: 'http://emulator:90/c-get'),
        );
        $this->expectException(ApiEmulatorAssertionFailedException::class);
        $this->expectExceptionMessage(
            <<<TEXT
            There was no matching emulator request at position 4 - got:
             - GET http://emulator:90/a-get
             - POST http://emulator:90/b-get
             - GET http://emulator:90/c-get
            TEXT
        );
        $subject->nthRequest(4);
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
