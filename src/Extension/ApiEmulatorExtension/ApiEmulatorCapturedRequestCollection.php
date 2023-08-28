<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Countable;
use function array_filter;
use function array_map;
use function sprintf;

class ApiEmulatorCapturedRequestCollection implements Countable
{
    /**
     * @var ApiEmulatorCapturedRequest[]
     */
    public readonly array $requests;

    public function __construct(ApiEmulatorCapturedRequest ...$requests)
    {
        $this->requests = $requests;
    }

    /**
     * Assert that there are no requests in the collection
     *
     * @throws ApiEmulatorAssertionFailedException
     */
    public function assertEmpty(): void
    {
        if ($this->requests !== []) {
            throw new ApiEmulatorAssertionFailedException(
                "Expected no API emulator requests but got:\n".$this->stringifyRequestList()
            );
        }
    }

    /**
     * Assert that there was exactly one request to a given method & URL (ignoring requests to anything else)
     *
     * @param string $method
     * @param string $uri
     *
     * @return ApiEmulatorCapturedRequest
     */
    public function assertSingleRequestTo(string $method, string $uri): ApiEmulatorCapturedRequest
    {
        if ($this->requests === []) {
            throw new ApiEmulatorAssertionFailedException(
                sprintf(
                    "Expected exactly one request to %s %s but the emulator did not capture any requests.",
                    $method,
                    $uri
                )
            );
        }

        $filtered = $this->filterByUriAndMethod($uri, $method);
        if (count($filtered->requests) !== 1) {
            throw new ApiEmulatorAssertionFailedException(
                sprintf(
                    "Expected exactly one request to %s %s but got:\n%s",
                    $method,
                    $uri,
                    $this->stringifyRequestList()
                )
            );
        }

        return $filtered->requests[0];
    }

    public function count(): int
    {
        return count($this->requests);
    }

    /**
     * Return a new collection containing only requests to the specified URI
     */
    public function filterByUri(string $uri): ApiEmulatorCapturedRequestCollection
    {
        return $this->filter(fn (ApiEmulatorCapturedRequest $rq) => $rq->uri === $uri);
    }

    /**
     * Return a new collection containing only requests to the specified URI with the specified method
     */
    public function filterByUriAndMethod(string $uri, string $method): ApiEmulatorCapturedRequestCollection
    {
        return $this->filter(
            fn (ApiEmulatorCapturedRequest $rq) => ($rq->uri === $uri) && ($rq->method === $method)
        );
    }

    /**
     * Return a new collection containing only requests that match the provided filter
     *
     * @param callable(ApiEmulatorCapturedRequest):bool $matcher
     */
    public function filter(callable $matcher): ApiEmulatorCapturedRequestCollection
    {
        $requests = array_filter($this->requests, $matcher);

        return new ApiEmulatorCapturedRequestCollection(...$requests);
    }

    /**
     * Get a request by sequence number (e.g. the first request n=1, second request n=2, etc)
     *
     * @throws ApiEmulatorAssertionFailedException if n is higher than the number of requests in the collection
     */
    public function nthRequest(int $n): ApiEmulatorCapturedRequest
    {
        if (count($this->requests) < $n) {
            throw new ApiEmulatorAssertionFailedException(
                sprintf(
                    "There was no matching emulator request at position %d - got:\n%s",
                    $n,
                    $this->stringifyRequestList()
                )
            );
        }

        return $this->requests[$n - 1];
    }

    private function stringifyRequestList(): string
    {
        return implode(
            "\n",
            array_map(fn (ApiEmulatorCapturedRequest $r) => ' - '.$r->method.' '.$r->uri, $this->requests)
        );
    }
}
