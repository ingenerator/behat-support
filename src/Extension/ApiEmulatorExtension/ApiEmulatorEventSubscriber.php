<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ApiEmulatorEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::BEFORE => ['beforeScenario', 10],
        ];
    }

    public function __construct(
        private readonly ApiEmulatorClient $client
    ) {
    }

    public function beforeScenario(): void
    {
        $this->client->deleteState();
    }


}
