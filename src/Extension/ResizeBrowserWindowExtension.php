<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension;


use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Ingenerator\BehatSupport\Extension\ResizeBrowserWindowExtension\ResizeBrowserWindowListener;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ResizeBrowserWindowExtension implements Extension
{
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->integerNode('window-width')
            ->defaultValue(1024)
            ->end()
            ->integerNode('window-height')
            ->defaultValue(768)
            ->end();
    }

    public function getConfigKey()
    {
        return 'browser_window';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        // no-op
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadListener($container, $config);
    }

    protected function loadListener(ContainerBuilder $container, array $config)
    {
        $definition = new Definition(
            ResizeBrowserWindowListener::class,
            [
                new Reference('mink'),
                $config,
            ]
        );
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => 0]);
        $container->setDefinition('resize_browser_window.listener', $definition);
    }

    public function process(ContainerBuilder $container)
    {
        // no-op
    }


}
