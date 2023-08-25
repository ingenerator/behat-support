<?php

namespace Ingenerator\BehatSupport\Extension;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Behat\Transformation\ServiceContainer\TransformationExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderArgumentTransformer;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderContextInitializer;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ScenarioPlaceholderExtension implements Extension
{
    private const MANAGER_SERVICE_ID = 'scenario_placeholder.manager';

    public function configure(ArrayNodeDefinition $builder)
    {
        // no-op
    }

    public function getConfigKey()
    {
        return 'scenario_placeholder';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        // no-op
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadManagerService($container);
        $this->loadContextInitializer($container);
        $this->loadArgumentTransformer($container);
    }

    public function process(ContainerBuilder $container)
    {
        //no-op
    }

    private function loadManagerService(ContainerBuilder $container): void
    {
        $definition = new Definition(ScenarioPlaceholderManager::class, []);
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => 0]);
        $container->setDefinition(self::MANAGER_SERVICE_ID, $definition);
    }

    private function loadContextInitializer(ContainerBuilder $container): void
    {
        $definition = new Definition(
            ScenarioPlaceholderContextInitializer::class,
            [new Reference(self::MANAGER_SERVICE_ID)]
        );
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);
        $container->setDefinition('scenario_placeholder.initializer', $definition);

    }

    private function loadArgumentTransformer(ContainerBuilder $container): void
    {
        $definition = new Definition(
            ScenarioPlaceholderArgumentTransformer::class,
            [new Reference(self::MANAGER_SERVICE_ID)]
        );
        $definition->addTag(TransformationExtension::ARGUMENT_TRANSFORMER_TAG, ['priority' => 0]);
        $container->setDefinition('scenario_placeholder.arg_transformer', $definition);
    }

}
