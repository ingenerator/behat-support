<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\Context\Context;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorAwareContext;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorAwareContextInitialiser;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorClient;
use PHPUnit\Framework\TestCase;

class ApiEmulatorAwareContextInitialiserTest extends TestCase
{
    private ApiEmulatorClient $emulator_client;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ApiEmulatorAwareContextInitialiser::class, $this->newSubject());
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
        $context = new class implements Context, ApiEmulatorAwareContext {
            public readonly ApiEmulatorClient $client;

            public function setApiEmulator(ApiEmulatorClient $client): void
            {
                $this->client = $client;
            }
        };

        $this->newSubject()->initializeContext($context);

        $this->assertSame($this->emulator_client, $context->client);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->emulator_client = new ApiEmulatorClient;
    }

    private function newSubject(): ApiEmulatorAwareContextInitialiser
    {
        return new ApiEmulatorAwareContextInitialiser(
            $this->emulator_client
        );
    }

}
