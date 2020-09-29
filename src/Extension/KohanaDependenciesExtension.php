<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension;


use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Ingenerator\BehatSupport\Extension\KohanaDependenciesExtension\KohanaDependenciesInitialiser;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class KohanaDependenciesExtension implements Extension
{
    public function configure(ArrayNodeDefinition $builder)
    {
        // No configuration
    }

    public function getConfigKey()
    {
        return 'kohana_dependencies';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        // No-op (this could be a place to warn if the bootstrapping extension isn't loaded)
    }

    public function load(ContainerBuilder $container, array $config)
    {
        if ( ! \class_exists('\Dependencies')) {
            throw new \RuntimeException('No `\Dependencies` class found : is Kohana bootstrapped?');
        }
        $container->set('kohana.dependencies', \Dependencies::instance());

        $this->loadInitialiser($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadInitialiser(ContainerBuilder $container)
    {
        $definition = new Definition(KohanaDependenciesInitialiser::class, [new Reference('kohana.dependencies')]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);
        $container->setDefinition('kohana_dependencies.initializer', $definition);
    }

    public function process(ContainerBuilder $container)
    {
        // No-op
    }


}
