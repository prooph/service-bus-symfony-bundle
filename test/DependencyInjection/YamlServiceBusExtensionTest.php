<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @covers \Prooph\Bundle\ServiceBus\DependencyInjection\Configuration
 * @covers \Prooph\Bundle\ServiceBus\DependencyInjection\ProophServiceBusExtension
 * @covers \Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\PluginsPass
 * @covers \Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\RoutePass
 * @covers \Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\StopwatchPass
 */
class YamlServiceBusExtensionTest extends AbstractServiceBusExtensionTestCase
{
    protected function buildContainer(): ContainerBuilder
    {
        return ContainerBuilder::buildContainer(function (SymfonyContainerBuilder $container) {
            return new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixture/config/yml'));
        }, 'yml');
    }
}
