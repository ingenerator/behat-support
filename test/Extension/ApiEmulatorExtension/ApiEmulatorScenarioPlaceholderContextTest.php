<?php

namespace test\Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorAwareContext;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorClient;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorScenarioPlaceholderContext;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderAwareContext;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderManager;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\UndefinedScenarioPlaceholderException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

class ApiEmulatorScenarioPlaceholderContextTest extends TestCase
{

    public function test_it_is_scenario_placeholder_and_api_emulator_aware()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf(ScenarioPlaceholderAwareContext::class, $subject);
        $this->assertInstanceOf(ApiEmulatorAwareContext::class, $subject);
    }


    /**
     * @testWith ["api_emulator", "base_url", "http://my-emulator.url.test:9000"]
     *           ["api_emulator", "base_ping_url", "http://my-emulator.url.test:9000/ping-200"]
     */
    public function test_it_registers_expected_placeholders($type, $arg, $expect)
    {
        $subject = $this->newSubject();
        $subject->setApiEmulator($this->stubEmulatorClientWithBaseUrl('http://my-emulator.url.test:9000'));

        $manager = new ScenarioPlaceholderManager;
        $subject->setScenarioPlaceholders($manager);

        $this->assertSame($expect, $manager->transform($type, $arg));
    }

    public function test_its_placeholder_throws_on_unexpected_arg()
    {
        $subject = $this->newSubject();
        $manager = new ScenarioPlaceholderManager();
        $subject->setScenarioPlaceholders($manager);

        $this->expectException(UndefinedScenarioPlaceholderException::class);
        $this->expectExceptionMessage('foobar');
        $manager->transform('api_emulator', 'foobar');
    }

    public function test_users_can_customise_the_placeholder_type()
    {
        $subject = new ApiEmulatorScenarioPlaceholderContext('my_random_type');
        $subject->setApiEmulator($this->stubEmulatorClientWithBaseUrl('http://my-emulator.url.test:9000'));

        $manager = new ScenarioPlaceholderManager;
        $subject->setScenarioPlaceholders($manager);

        $this->assertSame(
            'http://my-emulator.url.test:9000',
            $manager->transform('my_random_type', 'base_url')
        );
    }

    private function newSubject(): ApiEmulatorScenarioPlaceholderContext
    {
        return new ApiEmulatorScenarioPlaceholderContext;
    }

    private function stubEmulatorClientWithBaseUrl(string $base_url): ApiEmulatorClient
    {
        return new ApiEmulatorClient(new MockHttpClient, $base_url);
    }

}
