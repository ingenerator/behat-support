<?php

namespace Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

class ScenarioPlaceholderContextInitializer implements ContextInitializer
{
    public function __construct(
        private readonly ScenarioPlaceholderManager $manager
    )
    {
    }

    public function initializeContext(Context $context)
    {
        if ($context instanceof ScenarioPlaceholderAwareContext) {
            $context->setScenarioPlaceholders($this->manager);
        }
    }

}
