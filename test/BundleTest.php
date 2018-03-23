<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\PluginsPass;
use Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\RoutePass;
use Prooph\Bundle\ServiceBus\ProophServiceBusBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @covers \Prooph\Bundle\ServiceBus\ProophServiceBusBundle */
class BundleTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_compiler_pass()
    {
        $container = new ContainerBuilder();
        $bundle = new ProophServiceBusBundle();
        $bundle->build($container);

        $config = $container->getCompilerPassConfig();
        $passes = $config->getBeforeOptimizationPasses();

        $this->assertInstanceOf(PassConfig::class, $config);

        $hasPluginPass = false;
        $hasRoutePass = false;

        foreach ($passes as $pass) {
            if ($pass instanceof PluginsPass) {
                $hasPluginPass = true;
                continue;
            }
            if ($pass instanceof RoutePass) {
                $hasRoutePass = true;
                continue;
            }
        }

        $this->assertTrue($hasPluginPass, 'No plugin pass configured');
        $this->assertTrue($hasRoutePass, 'No route pass configured');
    }
}
