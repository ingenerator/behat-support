<?php

namespace Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension;

interface ScenarioPlaceholderAwareContext
{

    public function setScenarioPlaceholders(ScenarioPlaceholderManager $manager): void;
}
