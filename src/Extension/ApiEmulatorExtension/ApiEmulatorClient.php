<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function array_map;
use function json_decode;
use function ltrim;
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
     * Ensure that the emulator healthcheck is returning a 200, or fail
     *
     * @throws ApiEmulatorException if the emulator is not healthy
     */
    public function ensureHealthy(): void
    {
        try {
            $this->doRequestExpectingCode('GET', '/_emulator-meta/health', 200);
        } catch (TransportException $e) {
            throw new ApiEmulatorException(
                'Emulator request failed: [TransportException] '.$e->getMessage(),
                previous: $e
            );
        }
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

    /**
     * Populate arbitrary data on the emulator that handlers can read. Note this will be reset for each scenario.
     */
    public function populateRepository(string $path, array $data): void
    {
        $this->doRequestExpectingCode(
            'POST',
            '/_emulator-meta/handler-data/'.ltrim($path, '/'),
            200,
            ['json' => $data]
        );
    }

    private function doRequestExpectingCode(
        string $method,
        string $path,
        int $expect_code,
        array $options = []
    ): ResponseInterface {
        $url = $this->base_url.$path;
        $response = $this->http_client->request($method, $url, $options);

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
