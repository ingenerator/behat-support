<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

interface ApiEmulatorAwareContext
{
    public function setApiEmulator(ApiEmulatorClient $client): void;

}
