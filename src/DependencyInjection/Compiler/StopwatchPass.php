<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2017 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The Stopwatch Plugin requires the Stopwatch service from the FrameworkBundle.
 *
 * If such service is not registered, we have to de-register the plugin.
 */
class StopwatchPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('prooph_service_bus.plugin.stopwatch')
            && ! $container->hasDefinition('debug.stopwatch')
        ) {
            $container->removeDefinition('prooph_service_bus.plugin.stopwatch');
        }
    }
}
