<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\BehatSupport\Param;


class DateParam
{

    /**
     * Parses a relative date string in a more concise, powerful and predictable way than PHP's string handling
     *
     * Examples:
     *   * Y-m-d         -> today
     *   * Y-m-15        -> 15th of current month and year)
     *   * Y-m-(d+1)     -> tomorrow
     *   * (Y-1)-m-(d-1) -> 1 year ago yesterday
     *   * (Y+1)-m-13    -> 13th of this month next year
     *
     * Note that date logic will rollover even if explicit numeric values are used for some components. For example,
     * `2017-12-(d+1)` will produce `2017-12-21` on the 20th of the month, but on the 31st of a month it will produce
     * `2018-01-01`.
     *
     * @param string $string
     *
     * @return \DateTimeImmutable
     */
    public static function parse($string, \DateTimeImmutable $relative_to = NULL)
    {
        $relative_to = $relative_to ?: new \DateTimeImmutable('00:00:00');

        if (in_array($string, ['today', 'Y-m-d'])) {
            return $relative_to;
        }

        if ( ! preg_match(static::regex(), $string, $matches)) {
            throw new \InvalidArgumentException('Invalid date specification string `'.$string.'`');
        };

        $base_date = [];
        $modifiers = [];
        foreach (['d', 'm', 'Y'] as $component) {
            $base_date[$component] = $relative_to->format($component);
            $component_match       = $matches[$component];

            if ($component_match[0] === '(') {
                // This is a relative date specification
                $modifiers[$component] = $component_match;
            } elseif (is_numeric($component_match)) {
                // Use the explicit date value
                $base_date[$component] = $component_match;
            }
        }

        $date = $relative_to->setDate($base_date['Y'], $base_date['m'], $base_date['d']);

        foreach ($modifiers as $date_part => $modifier) {
            // modifier as `(d+4)`, `(m-3)`, etc
            $operator = $modifier[2];
            $interval = new \DateInterval('P'.substr($modifier, 3, -1).strtoupper($date_part));
            $date     = ($operator === '+') ? $date->add($interval) : $date->sub($interval);
        }

        return $date;
    }

    /**
     * @return string
     */
    private static function regex()
    {
        static $regex;
        if ( ! $regex) {
            $parts = [];
            foreach (['Y', 'm', 'd'] as $part) {
                $parts[] =
                    "(?<$part>"
                    .$part // simple Y/m/d to take date from relative date
                    .'|'
                    .'[0-9]+' // any explicit integer year / month / date value
                    .'|'
                    ."\\({$part}[+-][0-9]+\\)" // (d+3) for +3 days
                    .')';
            }
            $regex = '/^'.implode('-', $parts).'$/';
        }

        return $regex;
    }

}
