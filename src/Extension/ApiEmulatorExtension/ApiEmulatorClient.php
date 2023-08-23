<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

class ApiEmulatorClient
{

    public function __construct(
        public readonly string $base_url
    ) {

    }

    public function deleteState(): void
    {
        throw new \BadMethodCallException;
    }

}
