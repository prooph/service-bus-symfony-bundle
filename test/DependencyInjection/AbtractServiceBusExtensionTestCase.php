<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare (strict_types = 1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Bundle\ServiceBus\DependencyInjection\ProophServiceBusExtension;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use Prooph\ServiceBus\Plugin\Router\QueryRouter;
use Prooph\ServiceBus\QueryBus;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeRegisterUserCommand;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeRegisterUserHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;
use Symfony\Component\DependencyInjection\Dumper\YamlDumper;
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

        $router = $container->get('prooph_service_bus.command_bus_router.main');

        self::assertInstanceOf(CommandRouter::class, $router);
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

            $router = $container->get('prooph_service_bus.command_bus_router.main');

            self::assertInstanceOf(CommandRouter::class, $router);
        }
    }

    /**
     * @test
     */
    public function it_dumps_multiple_command_buses()
    {
        $this->dump('command_bus_multiple');
    }

    /**
     * @test
     */
    public function it_adds_default_container_plugin()
    {
        $container = $this->loadContainer('command_bus');

        /* @var $commandBus CommandBus */
        $commandBus = $container->get('prooph_service_bus.command_bus.main_bus');

        /** @var AcmeRegisterUserHandler $mockHandler */
        $mockHandler = $container->get('Acme\RegisterUserHandler');

        $command = new AcmeRegisterUserCommand(['name' => 'John Doe']);

        $commandBus->dispatch($command);

        self::assertSame($command, $mockHandler->lastCommand());
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

        $router = $container->get('prooph_service_bus.query_bus_router.main');

        self::assertInstanceOf(QueryRouter::class, $router);
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

            $router = $container->get('prooph_service_bus.query_bus_router.main');

            self::assertInstanceOf(QueryRouter::class, $router);
        }
    }

    /**
     * @test
     */
    public function it_dumps_multiple_query_buses()
    {
        $this->dump('query_bus_multiple');
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

        $router = $container->get('prooph_service_bus.event_bus_router.main');

        self::assertInstanceOf(EventRouter::class, $router);
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

            $router = $container->get('prooph_service_bus.event_bus_router.main');

            self::assertInstanceOf(EventRouter::class, $router);
        }
    }

    /**
     * @test
     */
    public function it_dumps_multiple_event_buses()
    {
        $this->dump('event_bus_multiple');
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

    private function dump(string $configFile)
    {
        $container = $this->loadContainer($configFile);
        $dumper = null;

        if ($this instanceof XmlServiceBusExtensionTest) {
            $dumper = new XmlDumper($container);
        } elseif ($this instanceof YamlServiceBusExtensionTest) {
            $dumper = new YamlDumper($container);
        }
        self::assertInstanceOf(Dumper::class, $dumper, sprintf('Test type "%s" not supported', get_class($this)));
        self::assertNotEmpty($dumper->dump());
    }
}
