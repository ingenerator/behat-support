<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use function array_filter;
use function array_map;

class ApiEmulatorCapturedRequestCollection
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

        $filtered = $this->filter(fn (ApiEmulatorCapturedRequest $r) => ($r->method === $method) && ($r->uri === $uri));
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

    private function filter(callable $matcher): ApiEmulatorCapturedRequestCollection
    {
        $requests = array_filter($this->requests, $matcher);

        return new ApiEmulatorCapturedRequestCollection(...$requests);
    }

    private function stringifyRequestList(): string
    {
        return implode(
            "\n",
            array_map(fn (ApiEmulatorCapturedRequest $r) => ' - '.$r->method.' '.$r->uri, $this->requests)
        );
    }
}
