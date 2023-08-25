<?php

namespace test\Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension;

use Behat\Behat\Context\Context;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderAwareContext;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderContextInitializer;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderManager;
use PHPUnit\Framework\TestCase;

class ScenarioPlaceholderContextInitialiserTest extends TestCase
{
    private ScenarioPlaceholderManager $manager;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ScenarioPlaceholderContextInitializer::class, $this->newSubject());
    }

    public function test_it_does_nothing_with_arbitrary_contexts()
    {
        $this->newSubject()->initializeContext(
            new class implements Context {
            }
        );
        $this->addToAssertionCount(1);
    }

    public function test_it_assigns_placeholder_manager_to_contexts_that_want_it()
    {
        $context = new class implements Context, ScenarioPlaceholderAwareContext {
            public readonly ScenarioPlaceholderManager $manager;

            public function setScenarioPlaceholders(ScenarioPlaceholderManager $manager): void
            {
                $this->manager = $manager;
            }
        };

        $this->newSubject()->initializeContext($context);

        $this->assertSame($this->manager, $context->manager);
    }

    protected function setUp(): void
    {
        $this->manager = new ScenarioPlaceholderManager;
        parent::setUp();
    }

    private function newSubject(): ScenarioPlaceholderContextInitializer
    {
        return new ScenarioPlaceholderContextInitializer($this->manager);
    }

}
