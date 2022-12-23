<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 Alexander Miertsch (http://getprooph.org/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus;

use Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\PluginsPass;
use Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\RoutePass;
use Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\StopwatchPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ProophServiceBusBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new StopwatchPass());
        $container->addCompilerPass(new PluginsPass());
        $container->addCompilerPass(new RoutePass());
    }
}
