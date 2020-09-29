<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension\KohanaDependenciesExtension;


use Ingenerator\KohanaExtras\DependencyContainer\DependencyContainer;

/**
 * Indicates this context needs to get additional dependencies from the application's dependency container
 */
interface DependenciesAwareContext
{

    public function setDependencies(DependencyContainer $dependencies);
}
