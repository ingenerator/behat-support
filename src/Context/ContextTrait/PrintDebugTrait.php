<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Context\ContextTrait;


/**
 * You don't really need this, but it's a useful little shorthand and a drop-in replacement for the
 * old base class method
 *
 * @package Ingenerator\BehatSupport\Extension
 */
trait PrintDebugTrait
{

    protected function printDebug($string)
    {
        \fwrite(STDOUT, "\n".$string."\n");
    }

}
