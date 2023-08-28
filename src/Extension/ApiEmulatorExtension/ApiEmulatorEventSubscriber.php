<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Ingenerator\PHPUtils\DateTime\Clock\RealtimeClock;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function implode;

class ApiEmulatorEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SuiteTested::BEFORE => ['beforeSuite', 10],
            ScenarioTested::BEFORE => ['beforeScenario', 10],
        ];
    }

    public function __construct(
        private readonly ApiEmulatorClient $client,
        private readonly int $healthcheck_timeout_seconds,
        private readonly int $healthcheck_retry_interval_ms,
        private readonly RealtimeClock $clock = new RealtimeClock,
    ) {
    }

    public function beforeSuite(): void
    {
        $timeout = $this->clock->getDateTime()->add(new \DateInterval('PT'.$this->healthcheck_timeout_seconds.'S'));
        $errors = [];

        while ($this->clock->getDateTime() < $timeout) {
            try {
                $this->client->ensureHealthy();

                return;
            } catch (ApiEmulatorException $e) {
                $errors[$e->getMessage()][] = 1;
                $this->clock->usleep($this->healthcheck_retry_interval_ms * 1000);
            }
        }

        $msg = ['API emulator healthcheck timed out after '.$this->healthcheck_timeout_seconds.' seconds:'];
        foreach ($errors as $err_msg => $instances) {
            $msg[] = sprintf(' - %d x %s', count($instances), $err_msg);
        }

        throw new ApiEmulatorException(implode("\n", $msg));
    }

    public function beforeScenario(): void
    {
        $this->client->deleteState();
    }


}
