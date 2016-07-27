<?php
declare(strict_types = 1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Bundle\ServiceBus\DependencyInjection\ProophServiceBusExtension;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\QueryBus;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

abstract class AbtractServiceBusExtensionTestCase extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    /**
     * @test
     */
    public function it_creates_a_command_bus()
    {
        $container = $this->loadContainer('command_bus');

        $config = $container->getDefinition('prooph_service_bus.command_bus.main_bus');

        self::assertEquals(CommandBus::class, $config->getClass());

        /* @var $commandBus CommandBus */
        $commandBus = $container->get('prooph_service_bus.command_bus.main_bus');

        self::assertInstanceOf(CommandBus::class, $commandBus);
    }

    /**
     * @test
     */
    public function it_creates_multiple_command_buses()
    {
        $container = $this->loadContainer('command_bus_multiple');

        foreach (['main', 'second'] as $name) {
            $config = $container->getDefinition('prooph_service_bus.command_bus.' . $name . '_bus');

            self::assertEquals(CommandBus::class, $config->getClass());

            /* @var $commandBus CommandBus */
            $commandBus = $container->get('prooph_service_bus.command_bus.' . $name . '_bus');

            self::assertInstanceOf(CommandBus::class, $commandBus);
        }
    }

    /**
     * @test
     */
    public function it_creates_a_query_bus()
    {
        $container = $this->loadContainer('query_bus');

        $config = $container->getDefinition('prooph_service_bus.query_bus.main_bus');

        self::assertEquals(QueryBus::class, $config->getClass());

        /* @var $queryBus QueryBus */
        $queryBus = $container->get('prooph_service_bus.query_bus.main_bus');

        self::assertInstanceOf(QueryBus::class, $queryBus);
    }

    /**
     * @test
     */
    public function it_creates_multiple_query_buses()
    {
        $container = $this->loadContainer('query_bus_multiple');

        foreach (['main', 'second'] as $name) {
            $config = $container->getDefinition('prooph_service_bus.query_bus.' . $name . '_bus');

            self::assertEquals(QueryBus::class, $config->getClass());

            /* @var $queryBus QueryBus */
            $queryBus = $container->get('prooph_service_bus.query_bus.' . $name . '_bus');

            self::assertInstanceOf(QueryBus::class, $queryBus);
        }
    }

    /**
     * @test
     */
    public function it_creates_an_event_bus()
    {
        $container = $this->loadContainer('event_bus');

        $config = $container->getDefinition('prooph_service_bus.event_bus.main_bus');

        self::assertEquals(EventBus::class, $config->getClass());

        /* @var $eventBus EventBus */
        $eventBus = $container->get('prooph_service_bus.event_bus.main_bus');

        self::assertInstanceOf(EventBus::class, $eventBus);
    }

    /**
     * @test
     */
    public function it_creates_multiple_event_buses()
    {
        $container = $this->loadContainer('event_bus_multiple');


        foreach (['main', 'second'] as $name) {
            $config = $container->getDefinition('prooph_service_bus.event_bus.' . $name . '_bus');

            self::assertEquals(EventBus::class, $config->getClass());

            /* @var $eventBus EventBus */
            $eventBus = $container->get('prooph_service_bus.event_bus.' . $name . '_bus');

            self::assertInstanceOf(EventBus::class, $eventBus);
        }
    }

    private function loadContainer($fixture, CompilerPassInterface $compilerPass = null)
    {
        $container = $this->getContainer();
        $container->registerExtension(new ProophServiceBusExtension());

        $this->loadFromFile($container, $fixture);

        if (null !== $compilerPass) {
            $container->addCompilerPass($compilerPass);
        }

        $this->compileContainer($container);

        return $container;
    }

    private function getContainer(array $bundles = [])
    {
        $map = [];

        foreach ($bundles as $bundle) {
            require_once __DIR__ . '/Fixture/Bundles/' . $bundle . '/' . $bundle . '.php';

            $map[$bundle] = 'Fixture\\Bundles\\' . $bundle . '\\' . $bundle;
        }

        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => $map,
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../src',
        ]));
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveDefinitionTemplatesPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();
    }
}
