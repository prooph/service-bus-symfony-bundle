<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types = 1);

namespace Prooph\Bundle\ServiceBus\DependencyInjection;

use Prooph\ServiceBus\{
    CommandBus,
    EventBus,
    QueryBus,
    Exception\RuntimeException,
    Plugin\MessageFactoryPlugin,
    Plugin\Router\CommandRouter,
    Plugin\Router\EventRouter,
    Plugin\Router\QueryRouter
};

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Defines and load message bus instances.
 */
final class ProophServiceBusExtension extends Extension
{
    private $buses = [
        'command_bus' => CommandBus::class,
        'event_bus' => EventBus::class,
        'query_bus' => QueryBus::class,
    ];

    public function getNamespace()
    {
        return 'http://getprooph.org/schemas/symfony-dic/prooph';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);


        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('service_bus.xml');

        foreach ($this->buses as $bus => $class) {
            if (!empty($config[$bus . 'es'])) {
                $this->busLoad($bus, $class, $config, $container, $loader);
            }
        }

        $this->addClassesToCompile([
            CommandBus::class,
            QueryBus::class,
            EventBus::class,
            CommandRouter::class,
            EventRouter::class,
            QueryRouter::class,
        ]);
    }

    /**
     * Loads bus configuration depending on type. For configuration examples, please take look at
     * test/DependencyInjection/Fixture/config files
     *
     * @param string $type
     * @param string $class
     * @param array $config
     * @param ContainerBuilder $container
     * @param XmlFileLoader $loader
     */
    private function busLoad(
        string $type,
        string $class,
        array $config,
        ContainerBuilder $container,
        XmlFileLoader $loader
    ) {
        $loader->load($type . '.xml');

        $typePlural = $type . 'es';
        $commandBuses = [];

        foreach (array_keys($config[$typePlural]) as $name) {
            $commandBuses[$name] = sprintf('prooph_service_bus.' . $type . '.%s_bus', $name);
        }
        $container->setParameter('prooph_service_bus.' . $typePlural, $commandBuses);

        $def = $container->getDefinition('prooph_service_bus.' . $type);
        $def->setClass($class);

        foreach ($config[$typePlural] as $name => $options) {
            $this->loadBus($type, $name, $options, $container);
        }
    }

    /**
     * Initializes service bus class with plugins and routes
     *
     * @param string $type
     * @param string $name
     * @param array $options
     * @param ContainerBuilder $container
     */
    private function loadBus(string $type, string $name, array $options, ContainerBuilder $container)
    {
        $commandBusDefinition = $container->setDefinition(
            sprintf('prooph_service_bus.%s.%s_bus', $type, $name),
            new DefinitionDecorator('prooph_service_bus.' . $type)
        );

        if (!empty($options['plugins'])) {
            foreach ($options['plugins'] as $index => $util) {
                if (!is_string($util) || !$container->has($util)) {
                    throw new RuntimeException(sprintf(
                        'Wrong message bus utility "%s" configured. It is unknown by the container.',
                        $util
                    ));
                }

                $commandBusDefinition->addMethodCall('utilize', [$container->get($util)]);
            }
        }

        $commandBusDefinition->addMethodCall(
            'utilize',
            [new MessageFactoryPlugin($container->get($options['message_factory']))]
        );

        if (!empty($options['router']['routes'])) {
            $commandBusDefinition->addMethodCall('utilize', [$this->attachRouter($options['router'])]);
        }
    }

    private function attachRouter(array $routerConfig)
    {
        $routerClass = (string)$routerConfig['type'];

        return new $routerClass($routerConfig['routes'] ?? []);
    }

}
