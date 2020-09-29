<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension;


use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Ingenerator\BehatSupport\Extension\PhantomJSControllerExtension\PhantomJSControllerListener;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PhantomJSControllerExtension implements Extension
{
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->integerNode('webdriver-port')
            ->defaultValue(8643)
            ->end()
            ->booleanNode('ignore-ssl-errors')
            ->defaultValue(TRUE)
            ->end()
            ->booleanNode('load-images')
            ->defaultValue(FALSE)
            ->end()
            ->integerNode('window-width')
            ->defaultValue(1024)
            ->end()
            ->integerNode('window-height')
            ->defaultValue(768)
            ->end();
    }

    public function getConfigKey()
    {
        return 'phantom_js';
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
            PhantomJSControllerListener::class,
            [
                new Reference('mink'),
                new Reference('cli.output'),
                $config,
            ]
        );
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => 0]);
        $container->setDefinition('phantom_js_controller.listener', $definition);
    }

    public function process(ContainerBuilder $container)
    {
        // no-op
    }


}
