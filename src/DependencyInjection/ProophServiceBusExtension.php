<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\DependencyInjection;

use Prooph\Bundle\ServiceBus\NamedMessageBus;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Defines and load message bus instances.
 */
final class ProophServiceBusExtension extends Extension
{
    const AVAILABLE_BUSES = [
        'command',
        'event',
        'query',
    ];

    public function getNamespace()
    {
        return 'http://getprooph.org/schemas/symfony-dic/prooph';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('service_bus.xml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.xml');
        }

        foreach (self::AVAILABLE_BUSES as $type) {
            if (! empty($config[$type . '_buses'])) {
                $this->busLoad($type, $config[$type . '_buses'], $container, $loader);
            }
        }
    }

    /**
     * Loads bus configuration depending on type. For configuration examples, please take look at
     * test/DependencyInjection/Fixture/config files
     */
    private function busLoad(
        string $type,
        array $config,
        ContainerBuilder $container,
        XmlFileLoader $loader
    ) {
        // load specific bus configuration e.g. command_bus
        $loader->load($type . '_bus.xml');

        $serviceBuses = [];
        foreach (array_keys($config) as $name) {
            $serviceBuses[$name] = 'prooph_service_bus.' . $name;
        }
        $container->setParameter("prooph_service_bus.{$type}_buses", $serviceBuses);

        // Add DataCollector
        if ($type !== 'query' && $container->getParameter('kernel.debug') && class_exists(Stopwatch::class)) {
            $container
                ->setDefinition(
                    sprintf('prooph_service_bus.plugin.symfony_data_collector.%s_bus', $type),
                    new ChildDefinition('prooph_service_bus.plugin.symfony_data_collector')
                )
                ->addArgument($type)
                ->addTag('data_collector', [
                    'id' => sprintf('prooph.%s_bus', $type),
                    'template' => '@ProophServiceBus/Collector/debug_view.html.twig',
                ]);
        }

        foreach ($config as $name => $options) {
            $this->loadBus($type, $name, $options, $container);
        }
    }

    /**
     * Initializes specific service bus class with plugins and routes. Each class dependency must be set via a container
     * or reference definition.
     *
     * @param string $type
     * @param string $name
     * @param array $options
     * @param ContainerBuilder $container
     * @throws \Symfony\Component\DependencyInjection\Exception\BadMethodCallException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Prooph\Bundle\ServiceBus\Exception\RuntimeException
     */
    private function loadBus(string $type, string $name, array $options, ContainerBuilder $container): void
    {
        $serviceBusId = 'prooph_service_bus.' . $name;
        $serviceBusDefinition = $container->setDefinition(
            $serviceBusId,
            new ChildDefinition('prooph_service_bus.' . $type . '_bus')
        );
        $serviceBusDefinition->setPublic(true);
        if (in_array(NamedMessageBus::class, class_implements($container->getDefinition('prooph_service_bus.'.$type.'_bus')->getClass()))) {
            $serviceBusDefinition->addMethodCall('setBusName', [$name]);
            $serviceBusDefinition->addMethodCall('setBusType', [$type]);
        }

        // Add plugin tag for plugins configured in the bus config
        foreach ($options['plugins'] as $pluginServiceId) {
            $serviceBusDefinition->addMethodCall('addPlugin', [new Reference($pluginServiceId), $pluginServiceId]);
        }

        // Configure context factory
        $container
            ->setDefinition(
                'prooph_service_bus.message_data_converter.' . $type,
                new ChildDefinition('prooph_service_bus.message_data_converter')
            )
            ->addArgument(new Reference($options['message_converter']));

        $contextFactoryId = sprintf('prooph_service_bus.message_context_factory.%s', $serviceBusId);
        $container
            ->setDefinition($contextFactoryId, new ChildDefinition('prooph_service_bus.message_context_factory'))
            ->addArgument(new Reference($options['message_data_converter']));

        // Collecting data for each configured service bus
        if ($type !== 'query' && $container->getParameter('kernel.debug') && class_exists(Stopwatch::class)) {
            $container
                ->setDefinition(
                    sprintf('%s.plugin.data_collector', $serviceBusId),
                    new ChildDefinition('prooph_service_bus.plugin.data_collector')
                )
                ->addArgument(new Reference($contextFactoryId))
                ->addArgument(new Reference(sprintf('prooph_service_bus.plugin.symfony_data_collector.%s_bus', $type)))
                ->addTag("prooph_service_bus.{$type}_bus.plugin");
        }

        // Logging for each configured service bus
        $container
            ->setDefinition(
                sprintf('%s.plugin.psr_logger', $serviceBusId),
                new ChildDefinition('prooph_service_bus.plugin.psr_logger')
            )
            ->setArguments([
                new Reference($contextFactoryId),
                new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('monolog.logger', ['channel' => sprintf('%s_bus.%s', $type, $name)])
            ->addTag(sprintf('prooph_service_bus.%s.plugin', $name));

        // define message factory
        $messageFactoryId = 'prooph_service_bus.message_factory.' . $name;
        $container->setDefinition($messageFactoryId, new ChildDefinition($options['message_factory']));

        // define message factory plugin
        $messageFactoryPluginId = 'prooph_service_bus.message_factory_plugin.' . $name;
        $messageFactoryPluginDefinition = new ChildDefinition('prooph_service_bus.message_factory_plugin');
        $messageFactoryPluginDefinition->setArguments([new Reference($messageFactoryId)]);
        $messageFactoryPluginDefinition->addTag(sprintf('prooph_service_bus.%s.plugin', $name));

        $container->setDefinition($messageFactoryPluginId, $messageFactoryPluginDefinition);

        // define router
        $routerId = null;
        if (! empty($options['router'])) {
            $routerId = 'prooph_service_bus.' . $name . '.router';
            $routerDefinition = new ChildDefinition($options['router']['type']);
            $routerDefinition->setArguments([$options['router']['routes'] ?? []]);
            if (isset($options['router']['async_switch'])) {
                $decoratedRouterId = 'prooph_service_bus.' . $name . '.decorated_router';

                $container->setDefinition($decoratedRouterId, $routerDefinition);

                // replace router definition with async switch message router
                $routerDefinition = new ChildDefinition('prooph_service_bus.async_switch_message_router');
                $routerDefinition->setArguments([
                    new Reference($decoratedRouterId),
                    new Reference($options['router']['async_switch']),
                ]);
            }
            $routerDefinition->addTag(sprintf('prooph_service_bus.%s.plugin', $name));

            $container->setDefinition($routerId, $routerDefinition);
        }

        // define service locator
        $routeTargets = array_values($options['router']['routes'] ?? []);
        if ($type === 'event') {
            $routeTargets = array_merge([], ...$routeTargets);
        }
        $routeTargets = array_unique($routeTargets);
        $serviceLocatorId = sprintf('%s.plugin.service_locator.locator', $name);
        $serviceLocator = $container->register($serviceLocatorId, ServiceLocator::class);
        $serviceLocator->addTag('container.service_locator');
        $serviceLocator->setArgument(0, array_map(function (string $id) {
            return new Reference($id);
        }, array_combine($routeTargets, $routeTargets)));

        // and the plugin for it
        $serviceLocatorPlugin = new ChildDefinition('prooph_service_bus.plugin.service_locator');
        $serviceLocatorPlugin->setArgument(0, new Reference($serviceLocatorId));
        $serviceLocatorPlugin->addTag(sprintf('prooph_service_bus.%s.plugin', $name));
        $container->setDefinition(sprintf('%s.plugin.service_locator', $name), $serviceLocatorPlugin);

        $container->setParameter(sprintf('prooph_service_bus.%s.configuration', $name), $options);
    }
}
