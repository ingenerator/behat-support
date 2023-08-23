<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorClient;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorEventSubscriber;
use PHPUnit\Framework\TestCase;

class ApiEmulatorEventSubscriberTest extends TestCase
{

    private ApiEmulatorClient $emulator_client;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ApiEmulatorEventSubscriber::class, $this->newSubject());
    }

    public function test_it_deletes_all_emulator_state_before_each_scenario()
    {
        $this->emulator_client = new class extends ApiEmulatorClient {
            public int $deleted = 0;

            public function __construct() { }

            public function deleteState(): void
            {
                $this->deleted++;
            }
        };

        $events = ApiEmulatorEventSubscriber::getSubscribedEvents();
        $method = $events[ScenarioTested::BEFORE][0];
        $subject = $this->newSubject();
        $subject->{$method}();
        $this->assertSame(1, $this->emulator_client->deleted, 'Should have deleted state');
    }

    public function test_it_waits_for_the_emulator_to_be_ready_before_starting_suite()
    {
        $this->markTestIncomplete();
    }

    public function test_it_times_out_if_emulator_is_not_healthy_at_the_start_of_the_suite()
    {
        $this->markTestIncomplete();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->emulator_client = new ApiEmulatorClient;
    }


    private function newSubject(): ApiEmulatorEventSubscriber
    {
        return new ApiEmulatorEventSubscriber(
            $this->emulator_client

        );
    }
}
