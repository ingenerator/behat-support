<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\Context\Context;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderAwareContext;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderManager;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\UndefinedScenarioPlaceholderException;

class ApiEmulatorScenarioPlaceholderContext implements Context, ScenarioPlaceholderAwareContext, ApiEmulatorAwareContext
{
    private readonly ApiEmulatorClient $emulator;

    public function __construct(
        private readonly string $placeholder_type_name = 'api_emulator',
    ) {
    }

    public function setApiEmulator(ApiEmulatorClient $client): void
    {
        $this->emulator = $client;
    }

    public function setScenarioPlaceholders(ScenarioPlaceholderManager $manager): void
    {
        $manager->registerCallback(
            $this->placeholder_type_name,
            fn ($arg) => match ($arg) {
                'base_url' => $this->emulator->base_url,
                'base_ping_url' => $this->emulator->base_url.'/ping-200',
                default => throw new UndefinedScenarioPlaceholderException(
                    sprintf(
                        'The `%s` placeholder type does not support an argument of "%s"',
                        $this->placeholder_type_name,
                        $arg
                    )
                ),
            }
        );
    }

}
