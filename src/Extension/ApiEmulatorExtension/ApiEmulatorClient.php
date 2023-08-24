<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function array_map;
use function json_decode;
use const JSON_THROW_ON_ERROR;

class ApiEmulatorClient
{

    public function __construct(
        private readonly HttpClientInterface $http_client,
        public readonly string $base_url
    ) {

    }

    /**
     * Reset emulator state. You don't normally need to call this, it will be called automatically before each scenario.
     */
    public function deleteState(): void
    {
        $this->doRequestExpectingCode('DELETE', '/_emulator-meta/global-state', 204);
    }

    /**
     * List all the requests captured since the last time the emulator state was reset
     */
    public function listRequests(): ApiEmulatorCapturedRequestCollection
    {
        $response = $this->doRequestExpectingCode('GET', '/_emulator-meta/requests', 200);
        $body = json_decode($response->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);

        return new ApiEmulatorCapturedRequestCollection(
            ...array_map(fn ($r) => new ApiEmulatorCapturedRequest(...$r), $body)
        );
    }

    private function doRequestExpectingCode(string $method, string $path, int $expect_code): ResponseInterface
    {
        $url = $this->base_url.$path;
        $response = $this->http_client->request($method, $url);

        if ($response->getStatusCode() !== $expect_code) {
            // Be explicit because otherwise we hit symfony's bonkers idea of randomly throwing HTTP exceptions from
            // the __destruct method whenever the object goes out of scope... :(
            throw new ApiEmulatorException(
                sprintf(
                    "Emulator request failed: got HTTP %s from %s %s (expected %s)",
                    $response->getStatusCode(),
                    $method,
                    $url,
                    $expect_code
                )
            );
        }

        return $response;
    }

}
