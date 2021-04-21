<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace test\Ingenerator\BehatSupport\Param;


use Ingenerator\BehatSupport\Param\DateParam;

class DateParamTest extends \PHPUnit\Framework\TestCase
{

    public function test_it_parses_string_to_date_time_immutable()
    {
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            DateParam::parse('today')
        );
    }

    /**
     * @testWith ["Y-m-d tomorrow"]
     *           ["Y+1-m-d"]
     *           ["Y-(m+1-d"]
     *           ["Y-(m*1)-d"]
     */
    public function test_it_throws_on_invalid_specification($string)
    {
        $this->expectException(\InvalidArgumentException::class);
        DateParam::parse($string);
    }

    /**
     * @testWith ["today"]
     *           ["Y-m-d"]
     */
    public function test_it_parses_current_date_relative_to_today_by_default($string)
    {
        $this->assertSame(\date('Y-m-d 00:00:00'), DateParam::parse($string)->format('Y-m-d H:i:s'));
    }

    /**
     * @testWith ["today"]
     *           ["Y-m-d"]
     */
    public function test_it_parses_current_date_relative_to_optional_reference_date($string)
    {
        $ref = new \DateTimeImmutable('2017-07-21');
        $this->assertEquals($ref, DateParam::parse($string, $ref));
    }

    /**
     * @testWith ["2017-04-18", "Y-m-09", "2017-04-09 00:00:00"]
     *           ["2017-03-18", "Y-05-d", "2017-05-18 00:00:00"]
     *           ["2017-03-18", "2019-m-d", "2019-03-18 00:00:00"]
     *           ["2017-03-18", "2019-05-07", "2019-05-07 00:00:00"]
     */
    public function test_it_parses_dates_with_simple_numeric_overrides($ref_date, $string, $expect)
    {
        $ref_date = \DateTimeImmutable::createFromFormat('!Y-m-d', $ref_date);
        $this->assertEquals($expect, DateParam::parse($string, $ref_date)->format('Y-m-d H:i:s'));

    }

    /**
     * @testWith ["2017-04-18", "Y-m-(d+3)", "2017-04-21 00:00:00"]
     *           ["2017-04-18", "Y-m-(d-3)", "2017-04-15 00:00:00"]
     *           ["2017-03-02", "Y-m-(d-5)", "2017-02-25 00:00:00"]
     *           ["2017-03-02", "Y-(m+1)-d", "2017-04-02 00:00:00"]
     *           ["2017-03-02", "Y-(m-2)-d", "2017-01-02 00:00:00"]
     *           ["2017-03-02", "Y-(m-5)-d", "2016-10-02 00:00:00"]
     *           ["2017-03-02", "(Y-2)-(m+1)-d", "2015-04-02 00:00:00"]
     *           ["2017-03-02", "(Y+3)-(m-2)-d", "2020-01-02 00:00:00"]
     *           ["2017-03-02", "(Y-1)-(m-5)-d", "2015-10-02 00:00:00"]
     */
    public function test_it_parses_dates_with_simple_offsets_including_rollover_of_month_and_year(
        $ref_date,
        $string,
        $expect
    ) {
        $ref_date = \DateTimeImmutable::createFromFormat('!Y-m-d', $ref_date);
        $this->assertEquals($expect, DateParam::parse($string, $ref_date)->format('Y-m-d H:i:s'));
    }


    /**
     * @testWith ["2017-04-18", "Y-(m+2)-08", "2017-06-08 00:00:00"]
     *           ["2017-04-18", "(Y+1)-m-03", "2018-04-03 00:00:00"]
     *           ["2017-04-18", "Y-03-(d+15)", "2017-04-02 00:00:00"]
     *           ["2017-11-18", "2018-(m+2)-d", "2019-01-18 00:00:00"]
     */
    public function test_it_applies_numeric_overrides_before_relative_offsets_for_rollover_support(
        $ref_date,
        $string,
        $expect
    ) {
        $ref_date = \DateTimeImmutable::createFromFormat('!Y-m-d', $ref_date);
        $this->assertEquals($expect, DateParam::parse($string, $ref_date)->format('Y-m-d H:i:s'));
    }

}
