<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */


namespace Ingenerator\BehatSupport\Mink;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;

class MinkBrowserChecker
{

    /**
     * Sugar to get/enforce a ChromeDriver instance with type hinting when necessary
     *
     * Occasionally we specifically need a ChromeDriver - e.g. to call additional methods that are
     * not part of the standard Mink interface. This should generally be avoided, but if necessary
     * this simplifies asserting the driver is correct and providing type-hinting to the IDE.
     *
     * @param \Behat\Mink\Session $session
     *
     * @return \DMore\ChromeDriver\ChromeDriver
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public static function requireChromeDriver(Session $session): ChromeDriver
    {
        return static::requireDriverType(ChromeDriver::class, $session);
    }


    /**
     * Sugar to get/enforce a GoutteDriver instance with type hinting when necessary
     *
     * Occasionally we specifically need a GoutteDriver - e.g. to call additional methods that are
     * not part of the standard Mink interface. This should generally be avoided, but if necessary
     * this simplifies asserting the driver is correct and providing type-hinting to the IDE.
     *
     * @param \Behat\Mink\Session $session
     *
     * @return \DMore\ChromeDriver\ChromeDriver
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public static function requireGoutteDriver(Session $session): GoutteDriver
    {
        return static::requireDriverType(GoutteDriver::class, $session);
    }

    private static function requireDriverType(string $class, Session $session): DriverInterface
    {
        $driver = $session->getDriver();
        if ($driver instanceof $class) {
            return $driver;
        }
        throw new UnsupportedDriverActionException('This step requires a '.$class, $driver);
    }

    /**
     * Guard against drivers which don't support JS
     *
     * @param Session $session
     */
    public static function requireJavascript(Session $session)
    {
        //guard against drivers which don't support JS
        $driver = $session->getDriver();
        $driver->evaluateScript('return true');

    }

}
