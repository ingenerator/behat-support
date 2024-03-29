<?php

namespace test\Ingenerator\BehatSupport\Assertion;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Ingenerator\BehatSupport\Assertion\Spin;
use PHPUnit\TextUI\RuntimeException;

/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */
class SpinTest extends \PHPUnit\Framework\TestCase
{
    public function test_it_returns_instance_from_static_constructor()
    {
        $this->assertInstanceOf(
            Spin::class,
            Spin::fn(function () { })
        );
    }

    public function test_it_returns_result_of_successful_assertion_without_retry()
    {
        $ran_count = 0;
        $this->assertSame(
            5,
            Spin::fn(
                function () use (&$ran_count) {
                    $ran_count++;

                    return 5;
                }
            )->forAttempts(3),
            'Should return assertion result'
        );
        $this->assertSame(1, $ran_count, 'Should run once');
    }

    public function test_it_returns_final_result_if_assertion_fails_then_succeeds()
    {
        $ran_count = 0;
        $this->assertSame(
            3,
            Spin::fn(
                function () use (&$ran_count) {
                    $ran_count++;
                    if ($ran_count === 3) {
                        return $ran_count;
                    }
                    throw new \RuntimeException('Failed attempt '.$ran_count);

                }
            )
                ->setDelayMs(0)
                ->forAttempts(5)
        );
    }

    public function test_it_bubbles_exception_after_final_retry()
    {
        $ran_count = 0;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed attempt 4');
        Spin::fn(
            function () use (& $ran_count) {
                $ran_count++;
                throw new \RuntimeException('Failed attempt '.$ran_count);
            }
        )
            ->setDelayMs(0)
            ->forAttempts(4);
    }

    public function test_it_does_not_delay_after_successful_assertion()
    {
        $spin = Spin::fn(function () { return 'ok'; })->setDelayMs(200);
        $this->assertRetryExecutionTimeBetweenMs($spin, 3, 0, 5);
    }

    public function test_it_has_default_delay_between_retries_if_not_customised()
    {
        Spin::$default_delay_ms = 10;
        $ran_count              = 0;
        $spin                   = Spin::fn(
            function () use (& $ran_count) {
                $ran_count++;
                if ($ran_count <= 2) {
                    throw new \RuntimeException('fail');
                }
            }
        );

        $this->assertRetryExecutionTimeBetweenMs($spin, 3, 20, 30);
    }

    public function test_it_uses_custom_delay_between_retries_if_set()
    {
        Spin::$default_delay_ms = 300;
        $ran_count              = 0;
        $spin                   = Spin::fn(
            function () use (& $ran_count) {
                $ran_count++;
                if ($ran_count <= 2) {
                    throw new \RuntimeException('fail');
                }
            }
        )->setDelayMs(5);

        $this->assertRetryExecutionTimeBetweenMs($spin, 3, 10, 15);

    }

    public function test_it_can_run_for_number_of_seconds_instead_of_number_of_retries()
    {
        $ran_count = 0;
        try {
            Spin::fn(
                function () use (& $ran_count) {
                    $ran_count++;
                    throw new \RuntimeException('Attempt '.$ran_count);
                }
            )
                ->setDelayMs(200)
                ->forSeconds(1);

            $this->fail('Should be failing assertion');
        } catch (\RuntimeException $e) { // no action
        }
        $this->assertLessThanOrEqual(5, $ran_count, 'Max 5 runs in 1 second');
        $this->assertGreaterThanOrEqual(4, $ran_count, 'Should fit at least 4 runs in 1 second');
    }

    public function test_it_bubbles_unsupported_driver_action_immediately()
    {
        $ran_count = 0;
        $this->expectException(UnsupportedDriverActionException::class);
        $this->expectExceptionMessage('Attempt 1');
        Spin::fn(
            function () use (& $ran_count) {
                $ran_count++;
                throw new UnsupportedDriverActionException('Attempt '.$ran_count);
            }
        )
            ->setDelayMs(0)
            ->forAttempts(3);
    }

    public function provider_custom_exception_filter()
    {
        return [
            [
                // Still throws after the allowed number of times regardless of the retry decider
                [
                    'callableResults' => [
                        new RuntimeException('Expected (1)'),
                        new RuntimeException('Expected (2)'),
                    ],
                    'retry'           => ["Expected (1)", "Expected (2)"],
                    'forAttempts'     => 2,
                ],
                "Expected (2)",
            ],
            [
                // Throws if an exception is not retryable
                [
                    'callableResults' => [
                        new RuntimeException('Expected (1)'),
                        new RuntimeException('Expected (2)'),
                        new RuntimeException('Unexpected (3)'),
                        "OK",
                    ],
                    'retry'           => ["Expected (1)", "Expected (2)"],
                    'forAttempts'     => 4,
                ],
                "Unexpected (3)",
            ],
            [
                // Succeeds if all exceptions are expected
                [
                    'callableResults' => [
                        new RuntimeException('Expected (1)'),
                        new RuntimeException('Expected (2)'),
                        new RuntimeException('Expected (3)'),
                        "OK",
                    ],
                    'retry'           => ["Expected (1)", "Expected (2)", "Expected (3)"],
                    'forAttempts'     => 4,
                ],
                NULL,
            ],
        ];
    }

    /**
     * @dataProvider provider_custom_exception_filter
     */
    public function test_it_supports_custom_exception_filter(array $setup, ?string $expect_throw)
    {
        $callableResults = $setup['callableResults'];
        $spin = Spin::fn(
            function () use (&$callableResults) {
                if (empty($callableResults)) {
                    throw new \UnderflowException("No behaviour defined for this callable execution");
                }
                $next = \array_shift($callableResults);
                if ($next instanceof \Exception) {
                    throw $next;
                }

                return $next;
            }
        )
            ->setDelayMs(0)
            ->setExceptionFilter(fn(\Throwable $e) => \in_array($e->getMessage(), $setup['retry']));

        try {
            $result = $spin->forAttempts($setup['forAttempts']);
            $this->assertSame("OK", $result);
        } catch (\Exception$e) {
            $this->assertSame($expect_throw, $e->getMessage(), "Expect correct exception");
        }
    }

    /**
     * @param $spin
     * @param $retry_count
     * @param $min
     * @param $max
     */
    protected function assertRetryExecutionTimeBetweenMs($spin, $retry_count, $min, $max)
    {
        $start = \microtime(TRUE);
        $spin->forAttempts($retry_count);
        $end    = \microtime(TRUE);
        $ran_ms = 1000 * ($end - $start);
        $this->assertGreaterThan($min, $ran_ms, 'Should be at least '.$min.'ms to run');
        $this->assertLessThan($max, $ran_ms, 'Should run in less than '.$max.'ms');
    }

}


if ( ! \class_exists(\Behat\Mink\Exception\UnsupportedDriverActionException::class)) {
    class FakeException extends \RuntimeException
    {
    }

    \class_alias(FakeException::class, \Behat\Mink\Exception\UnsupportedDriverActionException::class);
}

class StubUnsupportedDriverActionException extends \Behat\Mink\Exception\UnsupportedDriverActionException
{
    public function __construct($msg) { $this->message = $msg; }
}
