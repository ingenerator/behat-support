<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2013-2017 inGenerator Ltd
 * @licence   BSD
 */

namespace Ingenerator\BehatSupport\PhantomJS;

use Behat\Behat\Event\BaseScenarioEvent;
use Behat\Behat\Event\OutlineExampleEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\MinkExtension\Context\RawMinkContext;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Start PhantomJS in WebDriver mode the first time a javascript test runs
 *
 * To ensure clean state and avoid having to run phantomjs the whole time, the feature context will dynamically
 * start a PhantomJS WebDriver process just before running the first @javascript scenario. By default,
 * images are not loaded as this adds significant time and resource use to the test suite.
 *
 * The phantomJS instance started by this context will be terminated at the end of the suite.
 *
 * @package Ingenerator\BehatSupport\PhantomJS
 */
class PhantomJSControllerContext extends RawMinkContext
{
    /**
     * @var array
     */
    protected $phantom_options = [
        'webdriver'         => 8643,
        'ignore-ssl-errors' => TRUE,
        'load-images'       => FALSE,
        'window-width'      => 1024,
        'window-height'     => 768,
    ];

    /**
     * @var Process
     */
    protected $phantom_process;

    public function __construct(array $options = [])
    {
        $this->phantom_options = array_merge($this->phantom_options, $options);
    }

    /**
     * @param BaseScenarioEvent $event
     *
     * @BeforeScenario @javascript
     * @throws Exception
     * @return void
     */
    public function onBeforeJavascriptScenario(BaseScenarioEvent $event)
    {
        /** @var Process $phantom_process */
        static $phantom_process;
        
        if ($phantom_process) {
            if ( ! $phantom_process->isRunning()) {
                throw new ProcessFailedException($phantom_process);
            }
        } else {
            $phantom_process = $this->startPhantom();
            // Register our stopPhantom method to shut down PhantomJS after Behat has cleaned up
            $event->getDispatcher()->addListener(
                'afterSuite',
                function () use ($phantom_process) { $this->stopSession(); },
                -20
            );
            register_shutdown_function(
                function () use ($phantom_process) { $this->terminatePhantom($phantom_process); }
            );
        }
        $this->phantom_process = $phantom_process;
    }

    protected function startPhantom()
    {
        $cmd = 'exec phantomjs --webdriver='.$this->phantom_options['webdriver'];
        foreach (['ignore-ssl-errors', 'load-images'] as $bool_option) {
            $cmd .= " --$bool_option=".($this->phantom_options[$bool_option] ? 'true' : 'false');
        }

        $phantom_process = new Process($cmd);
        $phantom_process->start();

        // Wait to be sure it launches
        sleep(5);
        if ( ! $phantom_process->isRunning()) {
            throw new ProcessFailedException($phantom_process);
        }
        $this->printDebug('PhantomJS running as process '.$phantom_process->getPid());

        // Set the window size
        $this->getSession('selenium2')->resizeWindow(
            $this->phantom_options['window-width'],
            $this->phantom_options['window-height']
        );

        return $phantom_process;
    }

    protected function stopSession()
    {
        if ($this->getMink()->isSessionStarted('selenium2')) {
            $this->getMink()->getSession('selenium2')->stop();
        }
    }

    protected function terminatePhantom(Process $phantom_process)
    {
        if ($phantom_process->isRunning()) {
            $this->printDebug('Killing phantomjs process '.$phantom_process->getPid());
            $phantom_process->stop(10);
        }
    }

    /**
     * @AfterStep
     */
    public function onAfterStep(StepEvent $event)
    {
        if ( ! $this->phantom_process) {
            return;
        }

        $phantom_output = $this->phantom_process->getIncrementalOutput();
        $log_entries    = preg_split('/^(?=\[[A-Z]+ - [0-9]{4})/m', $phantom_output, -1, PREG_SPLIT_NO_EMPTY);
        $errors         = [];
        foreach ($log_entries as $log_entry) {
            if (preg_match('/^\[(ERROR|WARN)/', $log_entry)) {
                $errors[] = $log_entry;
            }
        }

        if ($errors) {
            $this->printDebug(
                'PhantomJS errors during '.$event->getStep()->getText().":\n".implode("\n", $errors)
            );
        }
    }

}
