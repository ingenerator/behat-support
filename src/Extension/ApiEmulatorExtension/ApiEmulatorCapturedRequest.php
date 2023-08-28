<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use function uniqid;

/**
 * Represents a request captured by the emulator
 */
class ApiEmulatorCapturedRequest
{

    /**
     * @internal use for testing
     */
    public static function stubWith(
        ?string $id = null,
        string $handler_pattern = '/^anything/',
        string $method = 'GET',
        string $uri = 'http://api-emulator/anything',
        array $headers = [],
        array $parsed_body = [],
    ): self {
        $id ??= uniqid();

        return new self(
            id: $id,
            handler_pattern: $handler_pattern,
            method: $method,
            uri: $uri,
            headers: $headers,
            parsed_body: $parsed_body
        );
    }

    /**
     * @see https://github.com/ingenerator/api_emulator/blob/main/src/RequestRecorder/CapturedRequest.php
     */
    public function __construct(
        public readonly string $id,
        public readonly string $handler_pattern,
        public readonly string $method,
        public readonly string $uri,
        public readonly array $headers,
        public readonly array $parsed_body,
    ) {

    }
}
