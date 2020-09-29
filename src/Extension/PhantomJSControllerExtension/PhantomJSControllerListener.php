<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension\PhantomJSControllerExtension;


use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PhantomJSControllerListener implements EventSubscriberInterface
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
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Process $phantom_process
     */
    protected $phantom_process;

    public function __construct(Mink $mink, OutputInterface $output, array $options)
    {
        $this->mink    = $mink;
        $this->output  = $output;
        $this->options = $options;
    }

    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::BEFORE => 'beforeScenario',
            SuiteTested::AFTER     => ['afterSuiteStopSession', -20],
            StepTested::AFTER      => 'afterStepCaptureOutput',
        ];
    }

    /**
     * Logs any warnings or errors produced by phantomjs to the console
     *
     * @param AfterStepTested $event
     */
    public function afterStepCaptureOutput(AfterStepTested $event)
    {
        if ( ! $this->phantom_process) {
            return;
        }

        $phantom_output = $this->phantom_process->getIncrementalOutput();
        $log_entries    = \preg_split('/^(?=\[[A-Z]+ - [0-9]{4})/m', $phantom_output, -1, PREG_SPLIT_NO_EMPTY);
        $errors         = [];
        foreach ($log_entries as $log_entry) {
            if (\preg_match('/^\[(ERROR|WARN)/', $log_entry)) {
                $errors[] = $log_entry;
            }
        }

        if ($errors) {
            \array_unshift($errors, '<error>PhantomJS logged errors during '.$event->getStep()->getText().':</error>');
            $this->output->writeln($errors);
        }
    }

    /**
     * Stops the mink session before phantomjs is terminated to avoid shutdown errors
     */
    public function afterSuiteStopSession()
    {
        if ( ! $this->phantom_process) {
            return;
        }
        if ($this->mink->isSessionStarted('selenium2')) {
            $this->mink->getSession('selenium2')->stop();
        }
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

        if ($this->phantom_process) {
            if ( ! $this->phantom_process->isRunning()) {
                throw new ProcessFailedException($this->phantom_process);
            }
        } else {
            $this->phantom_process = $this->startPhantom();
            \register_shutdown_function(function () { $this->terminatePhantom(); });
        }
    }

    /**
     * Triggered before the first @javascript scenario
     *
     * @return Process
     */
    protected function startPhantom()
    {
        $cmd = 'exec phantomjs --webdriver='.$this->options['webdriver-port'];
        foreach (['ignore-ssl-errors', 'load-images'] as $bool_option) {
            $cmd .= " --$bool_option=".($this->options[$bool_option] ? 'true' : 'false');
        }

        $phantom_process = new Process($cmd);
        $phantom_process->start();

        // Wait to be sure it launches
        \sleep(5);
        if ( ! $phantom_process->isRunning()) {
            throw new ProcessFailedException($phantom_process);
        }
        $this->output->writeln('<comment>PhantomJS running as process '.$phantom_process->getPid().'</comment>');

        // Set the window size - this has to start the session explicitly to do so
        $selenium = $this->mink->getSession('selenium2');
        $selenium->start();
        $selenium->resizeWindow($this->options['window-width'], $this->options['window-height']);

        return $phantom_process;
    }

    /**
     * Runs as a shutdown function to ensure that phantom is terminated even if we have a fatal error or similar
     */
    protected function terminatePhantom()
    {
        if ($this->phantom_process AND $this->phantom_process->isRunning()) {
            $this->output->writeln('<comment>Killing phantomjs process '.$this->phantom_process->getPid().'</comment>');
            $this->phantom_process->stop(10);
            $this->phantom_process = NULL;
        }
    }

}
