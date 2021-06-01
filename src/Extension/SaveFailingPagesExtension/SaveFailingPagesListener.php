<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension\SaveFailingPagesExtension;


use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SaveFailingPagesListener implements EventSubscriberInterface
{

    /**
     * @var string
     */
    protected $base_path;

    /**
     * @var int
     */
    protected $failure_index = 0;

    /**
     * @var Mink
     */
    protected $mink;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(Mink $mink, OutputInterface $output, $base_path)
    {
        $this->mink      = $mink;
        $this->output    = $output;
        $this->base_path = \str_replace('//', '/', $base_path.'/'.\date('Y-m-d-H-i-s'));
    }

    public static function getSubscribedEvents()
    {
        return [
            StepTested::AFTER => 'saveFailedPage',
        ];
    }

    public function saveFailedPage(AfterStepTested $event)
    {
        if ($event->getTestResult()->getResultCode() !== TestResult::FAILED) {
            return;
        }

        if ( ! $this->isSessionStarted()) {
            return;
        }

        // NB: if a previous scenario loaded a page it will still be in the browser
        $file     = $this->getOutputFilename($event);
        try {
            // Sometimes the failure is a browser-level issue, and so trying to get the current URL / HTML will also
            // fail. This has to be caught : uncaught exceptions from a behat listener will terminate the entire build
            // without even allowing the `progress` formatter to print the list of failed scenarios/steps making it very
            // hard to debug where & why this went wrong.
            $session  = $this->mink->getSession();
            $messages = [
                '',
                '<comment>Step failed on URL: '.$session->getCurrentUrl().'</comment>',
            ];

            $this->writeFile($file.'.html', $session->getPage()->getContent());
            $messages[] = "<comment>    Captured HTML to $file.html</comment>";

            if ($shot = $this->getScreenshotIfPossible($session)) {
                $this->writeFile($file.'.png', $shot);
                $messages[] = "<comment>    Captured screenshot to $file.png</comment>";
            }

            $this->output->writeln($messages);
        } catch (\Exception $e) {
            $this->output->writeln('<error>Could not capture failure page for '.\basename($file).'</error>');
            $this->output->writeln('<error>'.\get_class($e).': '.$e->getMessage().'</error>');
            $this->output->writeln('<error>'.$e->getTraceAsString().'</error>');
        }
    }

    /**
     * @return bool
     */
    protected function isSessionStarted()
    {
        if ( ! $this->mink->isSessionStarted()) {
            return FALSE;
        }

        // Browserkit driver will throw if you try to access the request / page before one has been loaded
        $driver = $this->mink->getSession()->getDriver();
        if ($driver instanceof BrowserKitDriver) {
            return (bool) $driver->getClient()->getRequest();
        }

        return TRUE;
    }

    protected function getOutputFilename(AfterStepTested $event)
    {
        return \sprintf(
            '%s/%03d-%s-L%s',
            $this->base_path,
            ++$this->failure_index,
            \str_replace('/', '_', $event->getFeature()->getFile()),
            $event->getStep()->getLine()
        );
    }

    /**
     * @param string $file
     * @param string $content
     */
    protected function writeFile($file, $content)
    {
        if (empty($content)) {
            $this->output->writeln('<comment>Warning: No content to write to '.$file.'</comment>');
            return;
        }

        $path = \dirname($file);
        if ( ! \is_dir($path)) {
            \mkdir($path, 0777, TRUE);
        }

        if ( ! \file_put_contents($file, $content)) {
            throw new \RuntimeException('Couldn\'t write to '.$file);
        }

    }

    /**
     * @param Session $session
     *
     * @return string
     */
    protected function getScreenshotIfPossible(Session $session)
    {
        try {
            return $session->getScreenshot();
        } catch (UnsupportedDriverActionException $e) {
            // Oh well
            return FALSE;
        }

    }


}
