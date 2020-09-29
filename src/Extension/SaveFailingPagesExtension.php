<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension;


use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Ingenerator\BehatSupport\Extension\SaveFailingPagesExtension\SaveFailingPagesListener;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SaveFailingPagesExtension implements Extension
{
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('output_path')
            ->defaultValue('%paths.base%/build/logs/behat-html/pages')
            ->end();
    }

    public function getConfigKey()
    {
        return 'save_failing_pages';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        // no-op
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $base_path             = $container->getParameter('paths.base');
        $config['output_path'] = \str_replace('%paths.base%', $base_path, $config['output_path']);

        $this->loadListener($container, $config);
    }

    protected function loadListener(ContainerBuilder $container, array $config)
    {
        $definition = new Definition(
            SaveFailingPagesListener::class,
            [
                new Reference('mink'),
                new Reference('cli.output'),
                $config['output_path'],
            ]
        );
        $definition->addTag(EventDispatcherExtension::SUBSCRIBER_TAG, ['priority' => 0]);
        $container->setDefinition('save_failing_pages.listener', $definition);

    }

    public function process(ContainerBuilder $container)
    {
        // no-op
    }

}
