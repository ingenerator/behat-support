<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension\KohanaDependenciesExtension;


use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Ingenerator\KohanaExtras\DependencyContainer\DependencyContainer;

/**
 * Provides application dependencies to contexts that need them
 *
 * @package Ingenerator\BehatSupport\Extension\KohanaDependenciesExtension
 */
class KohanaDependenciesInitialiser implements ContextInitializer
{
    /**
     * @var DependencyContainer
     */
    protected $dependencies;

    public function __construct(DependencyContainer $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof DependenciesAwareContext) {
            $context->setDependencies($this->dependencies);
        }
    }

}
