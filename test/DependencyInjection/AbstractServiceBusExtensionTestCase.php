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

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\PluginsPass;
use Prooph\Bundle\ServiceBus\DependencyInjection\Compiler\RoutePass;
use Prooph\Bundle\ServiceBus\DependencyInjection\ProophServiceBusExtension;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Exception\CommandDispatchException;
use Prooph\ServiceBus\Exception\MessageDispatchException;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use Prooph\ServiceBus\Plugin\Router\QueryRouter;
use Prooph\ServiceBus\QueryBus;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeRegisterUserCommand;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeRegisterUserHandler;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\AcmeUserWasRegisteredEvent;
use ProophTest\Bundle\ServiceBus\DependencyInjection\Fixture\Model\MockPlugin;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;
use Symfony\Component\DependencyInjection\Dumper\YamlDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

abstract class AbstractServiceBusExtensionTestCase extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    /**
     * @test
     */
    public function it_creates_a_command_bus()
    {
        $container = $this->loadContainer('command_bus');

        $config = $container->getDefinition('prooph_service_bus.main_command_bus');

        self::assertEquals(CommandBus::class, $config->getClass());

        /* @var $commandBus CommandBus */
        $commandBus = $container->get('prooph_service_bus.main_command_bus');

        self::assertInstanceOf(CommandBus::class, $commandBus);

        $router = $container->get('prooph_service_bus.main_command_bus.router');

        self::assertInstanceOf(CommandRouter::class, $router);
    }

    /**
     * @test
     */
    public function it_allows_a_minimal_bus_configuration()
    {
        $container = $this->loadContainer('command_bus_minimal');
        self::assertInstanceOf(CommandBus::class, $container->get('prooph_service_bus.main_command_bus'));
        self::assertInstanceOf(CommandRouter::class, $container->get('prooph_service_bus.main_command_bus.router'));
    }

    /**
     * @test
     */
    public function it_creates_multiple_command_buses()
    {
        $container = $this->loadContainer('command_bus_multiple');

        foreach (['main_command_bus', 'second_command_bus'] as $name) {
            $config = $container->getDefinition('prooph_service_bus.' . $name);

            self::assertEquals(CommandBus::class, $config->getClass());

            /* @var $commandBus CommandBus */
            $commandBus = $container->get('prooph_service_bus.' . $name);

            self::assertInstanceOf(CommandBus::class, $commandBus);

            $router = $container->get('prooph_service_bus.'.$name.'.router');

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
        $commandBus = $container->get('prooph_service_bus.main_command_bus');

        /** @var AcmeRegisterUserHandler $mockHandler */
        $mockHandler = $container->get('Acme\RegisterUserHandler');

        $command = new AcmeRegisterUserCommand(['name' => 'John Doe']);

        $commandBus->dispatch($command);

        self::assertSame($command, $mockHandler->lastCommand());
    }

    /**
     * @test
     */
    public function it_adds_plugins_based_on_tags()
    {
        $container = $this->loadContainer('plugins', new PluginsPass());

        /** @var MockPlugin $globalPlugin */
        $globalPlugin = $container->get('global_plugin');

        /** @var MockPlugin $commandTypePlugin */
        $commandTypePlugin = $container->get('command_type_plugin');

        /** @var MockPlugin $mainCommandBusPlugin */
        $mainCommandBusPlugin = $container->get('main_command_bus_plugin');

        $reset = function () use ($globalPlugin, $commandTypePlugin, $mainCommandBusPlugin) {
            $globalPlugin->reset();
            $commandTypePlugin->reset();
            $mainCommandBusPlugin->reset();
        };

        /* @var $mainCommandBus CommandBus */
        $mainCommandBus = $container->get('prooph_service_bus.main_command_bus');

        /* @var $secondCommandBus CommandBus */
        $secondCommandBus = $container->get('prooph_service_bus.second_command_bus');

        /* @var $mainEventBus EventBus */
        $mainEventBus = $container->get('prooph_service_bus.main_event_bus');

        try {
            $mainCommandBus->dispatch('a message');
        } catch (CommandDispatchException $ex) {
            //ignore
        }

        $this->assertTrue($globalPlugin->wasFired());
        $this->assertTrue($commandTypePlugin->wasFired());
        $this->assertTrue($mainCommandBusPlugin->wasFired());

        $reset();

        try {
            $secondCommandBus->dispatch('a message');
        } catch (CommandDispatchException $ex) {
            //ignore
        }

        $this->assertTrue($globalPlugin->wasFired());
        $this->assertTrue($commandTypePlugin->wasFired());
        $this->assertFalse($mainCommandBusPlugin->wasFired());

        $reset();

        try {
            $mainEventBus->dispatch('a message');
        } catch (MessageDispatchException $ex) {
            //ignore
        }

        $this->assertTrue($globalPlugin->wasFired());
        $this->assertFalse($commandTypePlugin->wasFired());
        $this->assertFalse($mainCommandBusPlugin->wasFired());
    }

    /**
     * @test
     */
    public function it_creates_a_query_bus()
    {
        $container = $this->loadContainer('query_bus');

        $config = $container->getDefinition('prooph_service_bus.main_query_bus');

        self::assertEquals(QueryBus::class, $config->getClass());

        /* @var $queryBus QueryBus */
        $queryBus = $container->get('prooph_service_bus.main_query_bus');

        self::assertInstanceOf(QueryBus::class, $queryBus);

        $router = $container->get('prooph_service_bus.main_query_bus.router');

        self::assertInstanceOf(QueryRouter::class, $router);
    }

    /**
     * @test
     */
    public function it_creates_multiple_query_buses()
    {
        $container = $this->loadContainer('query_bus_multiple');

        foreach (['main_query_bus', 'second_query_bus'] as $name) {
            $config = $container->getDefinition('prooph_service_bus.' . $name);

            self::assertEquals(QueryBus::class, $config->getClass());

            /* @var $queryBus QueryBus */
            $queryBus = $container->get('prooph_service_bus.' . $name);

            self::assertInstanceOf(QueryBus::class, $queryBus);

            $router = $container->get('prooph_service_bus.'.$name.'.router');

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

        $config = $container->getDefinition('prooph_service_bus.main_event_bus');

        self::assertEquals(EventBus::class, $config->getClass());

        /* @var $eventBus EventBus */
        $eventBus = $container->get('prooph_service_bus.main_event_bus');

        self::assertInstanceOf(EventBus::class, $eventBus);

        $router = $container->get('prooph_service_bus.main_event_bus.router');

        self::assertInstanceOf(EventRouter::class, $router);
    }

    /**
     * @test
     */
    public function it_creates_multiple_event_buses()
    {
        $container = $this->loadContainer('event_bus_multiple');

        foreach (['main_event_bus', 'second_event_bus'] as $name) {
            $config = $container->getDefinition('prooph_service_bus.' . $name);

            self::assertEquals(EventBus::class, $config->getClass());

            /* @var $eventBus EventBus */
            $eventBus = $container->get('prooph_service_bus.' . $name);

            self::assertInstanceOf(EventBus::class, $eventBus);

            $router = $container->get('prooph_service_bus.'.$name.'.router');

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

    /**
     * @test
     */
    public function it_allows_command_handlers_prefixed_with_at()
    {
        $container = $this->loadContainer('command_bus_routes_with_@');

        /* @var $commandBus CommandBus */
        $commandBus = $container->get('prooph_service_bus.main_command_bus');

        /** @var AcmeRegisterUserHandler $mockHandler */
        $mockHandler = $container->get('Acme\RegisterUserHandler');

        $command = new AcmeRegisterUserCommand(['name' => 'John Doe']);

        $commandBus->dispatch($command);

        self::assertSame($command, $mockHandler->lastCommand());
    }

    /**
     * @test
     */
    public function it_allows_event_listeners_prefixed_with_at()
    {
        $container = $this->loadContainer('event_bus_routes_with_@');

        $event = new AcmeUserWasRegisteredEvent([]);
        $eventBus = $container->get('prooph_service_bus.main_event_bus');
        $eventBus->dispatch($event);

        $mockListener = $container->get('Acme\UserListener');

        self::assertSame($event, $mockListener->lastEvent());
    }

    /**
     * @test
     */
    public function it_adds_command_bus_routes_based_on_tags_with_automatic_message_detection()
    {
        $container = $this->loadContainer('command_bus_with_tags', new RoutePass());

        /* @var $commandBus CommandBus */
        $commandBus = $container->get('prooph_service_bus.main_command_bus');

        /** @var AcmeRegisterUserHandler $mockHandler */
        $mockHandler = $container->get('Acme\RegisterUserHandler');

        $command = new AcmeRegisterUserCommand(['name' => 'John Doe']);

        $commandBus->dispatch($command);

        self::assertSame($command, $mockHandler->lastCommand());
    }

    /**
     * @test
     */
    public function it_adds_command_bus_routes_based_on_tags_with_message_configuration()
    {
        $container = $this->loadContainer('command_bus_with_tags_and_explicit_message', new RoutePass());

        /* @var $commandBus CommandBus */
        $commandBus = $container->get('prooph_service_bus.main_command_bus');

        /** @var AcmeRegisterUserHandler $mockHandler */
        $mockHandler = $container->get('Acme\RegisterUserHandler');

        $command = new AcmeRegisterUserCommand(['name' => 'John Doe']);

        $commandBus->dispatch($command);

        self::assertSame($command, $mockHandler->lastCommand());
    }

    /**
     * @test
     */
    public function it_adds_event_bus_routes_based_on_tags()
    {
        $container = $this->loadContainer('event_bus_with_tags', new RoutePass());

        $event = new AcmeUserWasRegisteredEvent([]);
        $eventBus = $container->get('prooph_service_bus.main_event_bus');
        $eventBus->dispatch($event);

        $mockListener = $container->get('Acme\UserListener');

        self::assertSame($event, $mockListener->lastEvent());
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
        } else {
            $dumper = new YamlDumper($container);
        }
        self::assertInstanceOf(Dumper::class, $dumper, sprintf('Test type "%s" not supported', get_class($this)));
        self::assertNotEmpty($dumper->dump());
    }
}
