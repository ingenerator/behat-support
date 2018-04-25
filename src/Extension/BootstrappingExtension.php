<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\BehatSupport\Extension;


use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BootstrappingExtension implements Extension
{

    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('bootstrap_path')
            ->defaultValue('%paths.base%/test/bootstrap-behat.php')
            ->end();
    }

    public function getConfigKey()
    {
        return 'bootstrapping';
    }

    public function initialize(ExtensionManager $extensionManager)
    {
        // No-op
    }

    public function load(ContainerBuilder $container, array $config)
    {
        $base_path = $container->getParameter('paths.base');
        $bootstrap = str_replace('%paths.base%', $base_path, $config['bootstrap_path']);
        if ( ! file_exists($bootstrap)) {
            throw new \InvalidArgumentException('No bootstrap file in '.$bootstrap);
        }
        require_once($bootstrap);
    }

    public function process(ContainerBuilder $container)
    {
        // No-op
    }

}
