<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\BehatSupport\Extension;


use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Ingenerator\BehatSupport\Extension\TestDataExtension\BehatDbInitialiser;
use Ingenerator\BehatSupport\Extension\TestDataExtension\BehatDbMaintainer;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TestDataExtension implements Extension
{
    public function configure(ArrayNodeDefinition $builder)
    {
        // No-op
    }

    public function getConfigKey()
    {
        return 'test_data';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        // No-op
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadMaintainer($container);
        $this->loadInitialiser($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadMaintainer(ContainerBuilder $container)
    {
        $definition = new Definition(BehatDbMaintainer::class, [new Reference('kohana.dependencies')]);
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => 0]);
        $container->setDefinition('test_data.maintainer', $definition);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadInitialiser(ContainerBuilder $container)
    {
        $definition = new Definition(BehatDbInitialiser::class, [new Reference('test_data.maintainer')]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);
        $container->setDefinition('test_data.initializer', $definition);
    }

    public function process(ContainerBuilder $container)
    {
        // No-op
    }

}
