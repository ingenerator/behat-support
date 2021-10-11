<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Assertion;


/**
 * Runs a callable assertion, waiting and retrying if required, and returns the result or throws
 * an exception.
 *
 *    Spin::fn(function () { $this->assertSomething('happened'); })
 *      ->setDelayMs(150) // or defaults to Spin::$default_delay_ms
 *      ->forRetries(5)   // retry up to 5 times
 *
 *    // or
 *
 *    Spin::fn(function () { $this->assertSomething('happened'); })
 *      ->setDelayMs(150)   // or defaults to Spin::$default_delay_ms
 *      ->forSeconds(1.5)   // retry for up to 1.5 seconds
 *
 * Optionally, pass a custom function to allow you to retry some exceptions but not others. In this example the
 * exception will be rethrown immediately without retrying unless the message is "Please wait".
 *
 *    Spin::fn(fn() => callMyApi())
 *      ->setExceptionFilter(fn(Exception $e) => $e->getMessage() === "Please wait")
 *      ->forSeconds(10);
 *
 * @package Ingenerator\BehatSupport\Assertion
 */
class Spin
{
    /**
     * @var int
     */
    public static $default_delay_ms = 100;

    /**
     * @var callable
     */
    protected $assertion;

    /**
     * @var int
     */
    protected $delay_ms;

    /**
     * @var callable
     */
    protected $exception_filter;

    /**
     *
     * @param callable $assertion
     *
     * @see Spin::fn
     */
    protected function __construct($assertion)
    {
        $this->assertion = $assertion;
        $this->delay_ms  = static::$default_delay_ms;
    }

    /**
     * @param callable $assertion
     *
     * @return Spin
     */
    public static function fn($assertion)
    {
        return new static($assertion);
    }

    /**
     * Run the assertion up to $retry_count times and return the result
     *
     * @param int $retry_count
     *
     * @return mixed
     */
    public function forAttempts($retry_count)
    {
        return $this->retryAssertionWhile(
            function () use (& $retries, $retry_count) {
                $retries++;

                return $retries < $retry_count;
            }
        );
    }

    /**
     * Run the assertion while the callback returns true
     *
     * @param callable $should_retry
     *
     * @return mixed
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     * @throws \Exception
     */
    protected function retryAssertionWhile($should_retry)
    {
        $last_exception = NULL;
        do {
            try {
                return \call_user_func($this->assertion);
            } catch (\Exception $e) {
                if ($this->shouldRetryException($e)) {
                    $last_exception = $e;
                } else {
                    throw $e;
                }
            }
            \usleep($this->delay_ms * 1000);
        } while ($should_retry());

        throw $last_exception;
    }

    protected function shouldRetryException(\Exception $e): bool
    {
        if ($e instanceof \Behat\Mink\Exception\UnsupportedDriverActionException) {
            return FALSE;
        }

        if ($this->exception_filter) {
            return ($this->exception_filter)($e);
        }

        return TRUE;
    }

    /**
     * Retry the assertion for up to the maximum number of seconds
     *
     * @param float $seconds
     *
     * @return mixed
     */
    public function forSeconds($seconds)
    {
        $end_by = \microtime(TRUE) + $seconds;

        return $this->retryAssertionWhile(
            function () use ($end_by) {
                return \microtime(TRUE) < $end_by;
            }
        );
    }

    /**
     * Set the number of milliseconds to wait between retries (defaults to Spin::$default_delay_ms)
     *
     * @param int $delay
     *
     * @return $this
     */
    public function setDelayMs($delay)
    {
        $this->delay_ms = $delay;

        return $this;
    }

    /**
     * @param callable $filter
     *
     * @return $this
     */
    public function setExceptionFilter(callable $filter)
    {
        $this->exception_filter = $filter;

        return $this;
    }

}
