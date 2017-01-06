<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2017 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

namespace ProophTest\Bundle\ServiceBus\DependencyInjection;

use Prooph\Bundle\ServiceBus\MessageBusFactory;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\QueryBus;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\MockPlugin;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MessageBusFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     * @dataProvider getBusTypes
     */
    public function it_attaches_plugins_to_bus_on_instantiation($busClass)
    {
        $busFactory = new MessageBusFactory();

        $pluginId = 'a.plugin.service.id';

        $mockPlugin = $this->prophesize(MockPlugin::class);
        $mockPlugin->attachToMessageBus(Argument::type($busClass))->shouldBeCalled();

        $containerInterface = $this->prophesize(ContainerInterface::class);
        $containerInterface->get($pluginId)->willReturn($mockPlugin->reveal());

        $pluginIds = [$pluginId];

        $newBus = $busFactory->create($busClass, $containerInterface->reveal(), $pluginIds);

        $this->assertInstanceOf($busClass, $newBus);
    }

    public function getBusTypes(): array
    {
        return [
            [EventBus::class],
            [CommandBus::class],
            [QueryBus::class],
        ];
    }
}
