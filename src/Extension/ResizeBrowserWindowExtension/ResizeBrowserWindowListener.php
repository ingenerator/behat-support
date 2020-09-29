<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension\ResizeBrowserWindowExtension;

use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Mink\Mink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResizeBrowserWindowListener implements EventSubscriberInterface
{
    /**
     * @var Mink
     */
    protected $mink;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var boolean
     */
    protected $is_resized = FALSE;

    /**
     * @param Mink  $mink
     * @param array $options
     */
    public function __construct(Mink $mink, array $options)
    {
        $this->mink    = $mink;
        $this->options = $options;
    }

    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::BEFORE => 'beforeScenario',
        ];
    }

    /**
     * Hook fired before every scenario that starts (or checks status of) phantomjs when first required
     *
     * @param BeforeScenarioTested $event
     */
    public function beforeScenario(BeforeScenarioTested $event)
    {
        if ( ! $event->getScenario()->hasTag('javascript')) {
            return;
        }

        if ($this->is_resized) {
            return;
        }

        // Set the window size - this has to start the session explicitly to do so
        $selenium = $this->mink->getSession('selenium2');
        $selenium->start();
        $selenium->resizeWindow($this->options['window-width'], $this->options['window-height']);
        $this->is_resized = TRUE;
    }

}

