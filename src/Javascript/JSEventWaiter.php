<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\BehatSupport\Javascript;


use Behat\Mink\Session;

/**
 * Watches for a javascript event on the page, optionally collecting event arguments for inspection.
 *
 * Note that you need to capture the event before calling the code that triggers it. For example:
 *
 *     // For simple cases where you just want to know it's happened
 *     $waiter = new JSEventWaiter($this->getSession());
 *     $waiter->capture('document', 'ajaxdone');
 *     $button->click();
 *     $waiter->waitFor('ajaxdone', 2000);
 *
 *     // For more complex cases where you need a value to inspect you can pass a custom JS handler
 *     // (use with care, you should usually test user-observable behaviour)
 *     $waiter = new JSEventWaiter($this->getSession());
 *     $waiter->capture('document', 'ajaxdone', 'function (e) { return {stuff: e.info} }');
 *     $button->click();
 *     $result = $waiter->waitFor('ajaxdone', 2000);
 *     $this->assertEquals('some info', $result['stuff']);
 *
 *
 * @package Ingenerator\BehatSupport\Javascript
 */
class JSEventWaiter
{
    /**
     * @var \Behat\Mink\Session
     */
    protected $mink;

    /**
     * @var string[]
     */
    protected $bound_event_handles = [];

    const BIND_SCRIPT = <<<'JS'
        return (function($, selector, bind_event, capture_func) {
            try {
                var result  = null,
                    $target = $(selector);

                if ($target.length == 0) {
                    throw new Error('No element matching `'+selector+'` to bind to');
                }
                
                $target.one(bind_event, function () { result = capture_func.apply(this, arguments)});

                window.BIND_HANDLE = {
                    isFired: function () { return result !== null; },
                    getResult: function () { return result; },
                }

                return {
                    ok: true
                }
             } catch (err) {
                return {
                    ok: false,
                    error: err.message
                };
             }
        })(window.jQuery, SELECTOR, BIND_EVENT, CAPTURE_FUNC);

JS;


    public function __construct(Session $mink)
    {
        $this->mink = $mink;
    }

    /**
     * Bind a randomly-named event handler to capture an event you expect to trigger.
     *
     * Use the capture_func argument if you need to collect values from the triggered event - this
     * will be called with the same arguments as the original event handler, and whatever it returns
     * will be returned from waitFor after the event fires.
     *
     * @param string $event        The event to catch
     * @param string $selector     The element to bind on
     * @param string $capture_func A javascript event handler if you need to capture custom data
     */
    public function capture($event, $selector, $capture_func = NULL)
    {
        if (isset($this->bound_event_handles[$event])) {
            throw new \InvalidArgumentException('Already bound to '.$event.' : cannot bind again');
        }

        $this->bound_event_handles[$event] = \uniqid('_event_'.$event);
        $script                            = \strtr(
            static::BIND_SCRIPT,
            [
                'BIND_HANDLE'  => $this->bound_event_handles[$event],
                'SELECTOR'     => $this->encodeSelector($selector),
                'BIND_EVENT'   => \json_encode($event),
                'CAPTURE_FUNC' => $capture_func ?: 'function () { return true; }'
            ]
        );

        $result = $this->mink->evaluateScript($script);
        if ( ! $result['ok']) {
            throw new \RuntimeException('Could not bind to `'.$event.'`: '.$result['error']);
        }
    }

    /**
     * Convert an element selector to a javascript parameter - `document`, `window` or otherwise an
     * encoded string eg `"#element"`.
     *
     * @param string $selector
     *
     * @return string
     */
    protected function encodeSelector($selector)
    {
        switch ($selector) {
            case 'document':
            case 'window':
                return $selector;
            default:
                return \json_encode($selector);
        }
    }

    /**
     * @param string $event
     * @param int    $timeout_ms
     *
     * @return mixed TRUE by default, or the result of your custom capture function
     */
    public function waitFor($event, $timeout_ms)
    {
        if ( ! isset($this->bound_event_handles[$event])) {
            throw new \InvalidArgumentException(
                'Cannot wait for `'.$event.'`: it has not been captured'
            );
        }

        $handler = 'window.'.$this->bound_event_handles[$event];
        if ( ! $this->mink->wait($timeout_ms, "$handler.isFired();")) {
            throw new \RuntimeException(
                'Timed out waiting for '.$event.' to fire (after '.$timeout_ms.'ms)'
            );
        }

        return $this->mink->evaluateScript("return $handler.getResult();");
    }

}
