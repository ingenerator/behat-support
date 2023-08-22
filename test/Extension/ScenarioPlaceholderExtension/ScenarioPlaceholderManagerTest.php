<?php

namespace test\Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ConflictingPlaceholderDefinitionException;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\InvalidScenarioPlaceholderException;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderManager;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\UndefinedScenarioPlaceholderException;
use PHPUnit\Framework\TestCase;
use function strrev;

class ScenarioPlaceholderManagerTest extends TestCase
{

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ScenarioPlaceholderManager::class, $this->newSubject());
    }

    public function provider_dates()
    {
        return [
            'tomorrow'  => ['Y-m-(d+1)', (new \DateTimeImmutable('tomorrow'))->format('Y-m-d')],
            'next year' => ['(Y+1)-03-02', (date('Y') + 1).'-03-02'],
        ];
    }

    /**
     * @dataProvider provider_dates
     */

    public function test_it_can_transform_date_params_out_of_the_box(string $arg, string $expect)
    {
        $this->assertSame($expect, $this->newSubject()->transform('date', $arg));
    }

    public function test_it_can_transform_registered_lookups()
    {
        $subject = $this->newSubject();
        $subject->registerLookup('customer_id', 'Brian Jones', '921');
        $this->assertSame('921', $subject->transform('customer_id', 'Brian Jones'));
    }

    public function test_it_throws_if_lookup_value_not_registered()
    {
        $subject = $this->newSubject();
        $subject->registerLookup('customer_id', 'Bill Graham', '912');
        $this->expectException(UndefinedScenarioPlaceholderException::class);
        $this->expectExceptionMessage('"Brian Jones" is not registered in the "customer_id" placeholder lookup table');
        $subject->transform('customer_id', 'Brian Jones');
    }

    public function test_it_throws_if_placeholder_type_not_registered()
    {
        $subject = $this->newSubject();
        $subject->registerLookup('customer_id', 'John G', '923');
        $this->expectException(UndefinedScenarioPlaceholderException::class);
        $this->expectExceptionMessage('"junk" is not a defined scenario placeholder type');
        $subject->transform('junk', 'whatever');
    }

    /**
     * @testWith  ["customer_id", "Bill K", "024"]
     *              ["reverse", "James", "semaJ"]
     */
    public function test_registered_transforms_are_reset_before_each_scenario($type, $arg, $expect_first_time)
    {
        $events  = ScenarioPlaceholderManager::getSubscribedEvents();
        $method  = $events[ScenarioTested::BEFORE][0];
        $subject = $this->newSubject();
        $subject->registerLookup('customer_id', 'Bill K', '024');
        $subject->registerCallback('reverse', strrev(...));

        $this->assertSame($expect_first_time, $subject->transform($type, $arg), 'Works before reset');
        $subject->{$method}();

        $this->expectException(UndefinedScenarioPlaceholderException::class);
        $subject->transform($type, $arg);
    }

    public function test_it_can_transform_with_registered_callbacks()
    {
        $subject = $this->newSubject();
        $subject->registerCallback('reverse', fn($arg) => strrev($arg));
        $this->assertSame('nairb', $subject->transform('reverse', 'brian'));
    }

    public function test_builtin_transforms_work_after_reset()
    {
        $subject = $this->newSubject();
        $before  = $subject->transform('date', 'Y-(m-2)-03');
        $subject->reset();
        $this->assertSame($before, $subject->transform('date', 'Y-(m-2)-03'));
    }

    public function test_cannot_register_lookup_that_conflicts_with_callbacks()
    {
        $subject = $this->newSubject();
        $subject->registerCallback('customer', fn($arg) => $arg);
        $this->expectException(ConflictingPlaceholderDefinitionException::class);
        $subject->registerLookup('customer', 'Brian', '123');
    }

    public function test_cannot_register_callback_that_conflicts_with_lookup()
    {
        $subject = $this->newSubject();
        $subject->registerLookup('customer', 'Brian', '123');
        $this->expectException(ConflictingPlaceholderDefinitionException::class);
        $subject->registerCallback('customer', fn($arg) => $arg);
    }

    private function newSubject(): ScenarioPlaceholderManager
    {
        return new ScenarioPlaceholderManager;
    }
}
