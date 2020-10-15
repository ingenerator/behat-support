<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */


namespace Ingenerator\BehatSupport\Mink;

use Behat\Mink\Session;

class MinkBrowserChecker
{
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
