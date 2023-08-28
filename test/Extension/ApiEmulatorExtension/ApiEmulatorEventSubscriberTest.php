<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorClient;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorEventSubscriber;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorException;
use Ingenerator\PHPUtils\DateTime\Clock\RealtimeClock;
use Ingenerator\PHPUtils\DateTime\Clock\StoppedMockClock;
use PHPUnit\Framework\TestCase;
use function array_shift;

class ApiEmulatorEventSubscriberTest extends TestCase
{

    private ApiEmulatorClient $emulator_client;

    private RealtimeClock $clock;

    private int $healthcheck_timeout_seconds = 10;

    private int $healthcheck_retry_interval_ms = 300;

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

        $this->handleEventOnSubject(ScenarioTested::BEFORE, $this->newSubject());

        $this->assertSame(1, $this->emulator_client->deleted, 'Should have deleted state');
    }

    public function provider_wait_ready_happy_cases()
    {
        return [
            'already healthy' => [
                [],
                1,
                [],
            ],
            'fail then OK' => [
                [
                    new ApiEmulatorException('Unreachable'),
                ],
                2,
                [250_000],
            ],
            'fail twice then OK' => [
                [
                    new ApiEmulatorException('Unreachable'),
                    new ApiEmulatorException('Unreachable'),
                ],
                3,
                [250_000, 250_000],
            ],
        ];
    }

    /**
     * @dataProvider provider_wait_ready_happy_cases
     */
    public function test_it_waits_for_the_emulator_to_be_ready_before_starting_suite(
        array $healtcheck_fails,
        int $expect_checks,
        array $expect_sleeps
    ) {
        $this->healthcheck_retry_interval_ms = 250;
        $this->clock = StoppedMockClock::atNow();

        $this->emulator_client = new class ($healtcheck_fails) extends ApiEmulatorClient {
            public int $healthchecks = 0;

            public function __construct(private array $healthcheck_fails) { }

            public function ensureHealthy(): void
            {
                $this->healthchecks++;
                $result = array_shift($this->healthcheck_fails);
                if ($result) {
                    throw $result;
                }
            }
        };

        $this->handleEventOnSubject(SuiteTested::BEFORE, $this->newSubject());

        $this->assertSame(
            $expect_checks,
            $this->emulator_client->healthchecks,
            'Should only have performed one healthcheck'
        );

        if (empty($expect_sleeps)) {
            $this->clock->assertNeverSlept();
        } else {
            $this->clock->assertSlept($expect_sleeps, 'Should never have slept');
        }
    }

    public function provider_healthcheck_timeout()
    {
        return [
            '3 seconds' => [
                3,
                <<<TEXT
                API emulator healthcheck timed out after 3 seconds:
                 - 6 x There was a problem
                TEXT,
            ],
            '5 seconds' => [
                5,
                <<<TEXT
                API emulator healthcheck timed out after 5 seconds:
                 - 6 x There was a problem
                 - 4 x Port unreachable
                TEXT,
            ],
        ];
    }

    /**
     * @dataProvider provider_healthcheck_timeout
     */
    public function test_it_throws_if_emulator_is_not_healthy_within_timeout_at_the_start_of_the_suite(
        int $timeout_seconds,
        string $expect_message
    ) {
        $this->healthcheck_timeout_seconds = $timeout_seconds;
        $this->healthcheck_retry_interval_ms = 500;
        $this->clock = StoppedMockClock::atNow();

        $this->emulator_client = new class extends ApiEmulatorClient {
            public function __construct() { }

            private int $healthchecks = 0;

            public function ensureHealthy(): void
            {
                $this->healthchecks++;
                if ($this->healthchecks <= 6) {
                    throw new ApiEmulatorException('There was a problem');
                } else {
                    throw new ApiEmulatorException('Port unreachable');
                }
            }
        };

        $subject = $this->newSubject();

        $this->expectException(ApiEmulatorException::class);
        $this->expectExceptionMessage($expect_message);
        $this->handleEventOnSubject(SuiteTested::BEFORE, $subject);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = StoppedMockClock::atNow();
        $this->emulator_client = new class extends ApiEmulatorClient {
            public function __construct() { }
        };
    }


    private function newSubject(): ApiEmulatorEventSubscriber
    {
        return new ApiEmulatorEventSubscriber(
            $this->emulator_client,
            $this->healthcheck_timeout_seconds,
            $this->healthcheck_retry_interval_ms,
            $this->clock,
        );
    }

    private function handleEventOnSubject(string $event, ApiEmulatorEventSubscriber $subject): void
    {
        $events = ApiEmulatorEventSubscriber::getSubscribedEvents();
        $method = $events[$event][0];
        $subject->{$method}();
    }
}
