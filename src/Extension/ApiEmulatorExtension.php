<?php

namespace Ingenerator\BehatSupport\Extension;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorAwareContextInitialiser;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorClient;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorEventSubscriber;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\HttpClient;

class ApiEmulatorExtension implements Extension
{
    const EMULATOR_HTTP_CLIENT_SERVICE_ID = 'api_emulator_client.http_client';
    const EMULATOR_CLIENT_SERVICE_ID = 'api_emulator_client';

    public function getConfigKey()
    {
        return 'api_emulator';
    }

    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('base_url')
            ->defaultValue('http://api-emulator-http:9000')
            ->end();
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        // no-op
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadEmulatorHttpClientService($container);
        $this->loadEmulatorClientService($container, $config);
        $this->loadContextInitializer($container);
        $this->loadEventListener($container);
    }

    private function loadEmulatorHttpClientService(ContainerBuilder $container_builder): void
    {
        $definition = new Definition(HttpClient::class);
        $definition->setFactory(HttpClient::class.'::create');
        $container_builder->setDefinition(self::EMULATOR_HTTP_CLIENT_SERVICE_ID, $definition);
    }


    private function loadEmulatorClientService(ContainerBuilder $container, array $config): void
    {
        $container->setDefinition(
            self::EMULATOR_CLIENT_SERVICE_ID,
            new Definition(
                ApiEmulatorClient::class,
                [
                    new Reference(self::EMULATOR_HTTP_CLIENT_SERVICE_ID),
                    $config['base_url'],
                ]
            )
        );
    }

    private function loadContextInitializer(ContainerBuilder $container): void
    {
        $definition = new Definition(
            ApiEmulatorAwareContextInitialiser::class,
            [new Reference(self::EMULATOR_CLIENT_SERVICE_ID)]
        );
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);
        $container->setDefinition('api_emulator.initializer', $definition);
    }

    private function loadEventListener(ContainerBuilder $container)
    {
        $definition = new Definition(
            ApiEmulatorEventSubscriber::class,
            [new Reference(self::EMULATOR_CLIENT_SERVICE_ID)]
        );
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => 0]);
        $container->setDefinition('api_emulator.listener', $definition);
    }

    public function process(ContainerBuilder $container)
    {
        // no-op
    }


}
