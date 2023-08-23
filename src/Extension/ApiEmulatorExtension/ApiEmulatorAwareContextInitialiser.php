<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

class ApiEmulatorAwareContextInitialiser implements ContextInitializer
{
    public function __construct(
        private readonly ApiEmulatorClient $emulator
    ) {

    }

    public function initializeContext(Context $context)
    {
        if ($context instanceof ApiEmulatorAwareContext) {
            $context->setApiEmulator($this->emulator);
        }
    }

}
