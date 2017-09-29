<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\DependencyInjection\Compiler;

use Prooph\Bundle\ServiceBus\DependencyInjection\ProophServiceBusExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach (ProophServiceBusExtension::AVAILABLE_BUSES as $type) {
            if (! $container->hasParameter('prooph_service_bus.' . $type . '_buses')) {
                continue;
            }

            $buses = $container->getParameter('prooph_service_bus.' . $type . '_buses');

            foreach ($buses as $name => $bus) {
                $globalPlugins = $container->findTaggedServiceIds('prooph_service_bus.plugin');
                $typePlugins = $container->findTaggedServiceIds(sprintf('prooph_service_bus.%s.plugin', $type . '_bus'));
                $localPlugins = $container->findTaggedServiceIds(sprintf('prooph_service_bus.%s.plugin', $name));

                $plugins = array_merge(array_keys($globalPlugins), array_keys($typePlugins), array_keys($localPlugins));

                $busDefinition = $container->getDefinition($bus);

                foreach ($plugins as $plugin) {
                    $busDefinition->addMethodCall('addPlugin', [new Reference($plugin), $plugin]);
                }
            }
        }
    }
}
