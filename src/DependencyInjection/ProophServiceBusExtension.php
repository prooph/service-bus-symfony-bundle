<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare (strict_types = 1);

namespace Prooph\Bundle\ServiceBus\DependencyInjection;

use Prooph\Bundle\ServiceBus\Exception\RuntimeException;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\QueryBus;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use Prooph\ServiceBus\Plugin\Router\QueryRouter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Defines and load message bus instances.
 */
final class ProophServiceBusExtension extends Extension
{
    private $availableBuses = [
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

        foreach ($this->availableBuses as $bus => $class) {
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
        $serviceBuses = [];

        foreach (array_keys($config[$typePlural]) as $name) {
            $serviceBuses[$name] = sprintf('prooph_service_bus.' . $type . '.%s_bus', $name);
        }
        $container->setParameter('prooph_service_bus.' . $typePlural, $serviceBuses);

        $def = $container->getDefinition('prooph_service_bus.' . $type);
        $def->setClass($class);

        foreach ($config[$typePlural] as $name => $options) {
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
    private function loadBus(string $type, string $name, array $options, ContainerBuilder $container)
    {
        $serviceBusDefinition = $container->setDefinition(
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

                $serviceBusDefinition->addMethodCall('utilize', [new Reference($util)]);
            }
        }
        // define message factory plugin
        $messageFactoryId = 'prooph_service_bus.message_factory_plugin.' . $name;

        $container
            ->setDefinition(
                $messageFactoryId,
                new DefinitionDecorator('prooph_service_bus.message_factory_plugin')
            )
            ->setArguments([new Reference($options['message_factory'])]);

        $serviceBusDefinition->addMethodCall('utilize', [new Reference($messageFactoryId)]);


        // define router
        if (!empty($options['router'])) {
            $routerId = sprintf('prooph_service_bus.%s.%s', $type . '_router', $name);

            $routerDefinition = $container->setDefinition(
                $routerId,
                new DefinitionDecorator('prooph_service_bus.' . $type . '_router')
            );
            $routerDefinition->setClass($options['router']['type']);
            $routerDefinition->setArguments([$options['router']['routes'] ?? []]);

            $serviceBusDefinition->addMethodCall('utilize', [new Reference($routerId)]);
        }
        
        //Add container plugin
        $containerWrapperId = 'prooph_service_bus.container_wrapper.' . $name;

        $containerWrapperDefinition = $container->setDefinition(
            $containerWrapperId,
            new DefinitionDecorator('prooph_service_bus.container_wrapper')
        );

        $containerWrapperDefinition->setArguments([new Reference('service_container')]);
        
        $containerPluginId = 'prooph_service_bus.container_plugin.' . $name;

        $containerPluginDefinition = $container->setDefinition(
            $containerPluginId,
            new DefinitionDecorator('prooph_service_bus.container_plugin')
        );

        $containerPluginDefinition->setArguments([new Reference($containerWrapperId)]);

        $serviceBusDefinition->addMethodCall('utilize', [new Reference($containerPluginId)]);
    }
}
