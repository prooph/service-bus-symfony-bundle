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

use Prooph\Common\Messaging\MessageFactory;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use Prooph\ServiceBus\Plugin\Router\QueryRouter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    private $debug;

    private $availableBuses = ['command_bus', 'event_bus', 'query_bus'];

    /**
     * Constructor
     *
     * @param Boolean $debug Whether to use the debug mode
     */
    public function __construct($debug)
    {
        $this->debug = (Boolean)$debug;
    }

    /**
     * Normalizes XML config and defines config tree
     *
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('prooph_service_bus');

        // cycle through bus types and add XML normalization
        foreach ($this->availableBuses as $bus) {
            $rootNode
                ->beforeNormalization()
                ->ifTrue
                (
                    // check for XML config neither command_buses or command_bus, but not both
                    function ($v) use ($bus) {
                        return is_array($v) && !array_key_exists('command_buses', $v) && !array_key_exists('command_bus', $v);
//                        return !isset($v[$bus . 'es']) && isset($v[$bus]) && !isset($v[$bus]) && is_array($v[$bus]);
                    }
                )
                ->then(function ($v) use ($bus) {
                    // normalize XML config
                    $command_bus = [];
                    foreach ($v[$bus] as $key => $value) {
                        $command_bus[$key] = $v[$bus][$key];
                    }
                    unset($v[$bus]);
                    $v[$bus . 'es'] = $command_bus;

                    return $v;
                });
        }

        $this->addServiceBusSections('command',  CommandRouter::class, $rootNode);
//        $this->addServiceBusSections('event',  EventRouter::class, $rootNode);
//        $this->addServiceBusSections('query',  QueryRouter::class, $rootNode);

        return $treeBuilder;
    }

    /**
     * Add service bus section to configuration tree
     *
     * @link https://github.com/prooph/service-bus
     *
     * @param ArrayNodeDefinition $node
     */
    private function addServiceBusSections(string $type, string $routerClass, ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig($type . '_bus', $type . '_buses')
            ->children()
            ->arrayNode($type . '_buses')
                ->beforeNormalization()
                    ->always(function ($v) {

                        $bus = 'command';
                        // normalize XML config

                        return $v;
                        $command_bus = [];
                        foreach ($v[$bus] as $key => $value) {
                            $command_bus[$key] = $v[$bus][$key];
                        }
                        unset($v[$bus]);
                        $v[$bus . 'es'] = $command_bus;

                        return $v;
                    })
                    ->end()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->fixXmlConfig('plugin', 'plugins')
                ->children()
                    ->scalarNode('message_factory')->defaultValue(MessageFactory::class)->end()
                    ->arrayNode('plugins')
                        ->beforeNormalization()
                            // fix single node in XML
                            ->ifString()->then(function ($v) { return [$v];})
                        ->end()
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('router')
                        ->fixXmlConfig('route', 'routes')
                        ->children()
                            ->scalarNode('type')->defaultValue($routerClass)->end()
                            ->arrayNode('routes')
                                ->useAttributeAsKey($type)
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            // dbal
//            ->fixXmlConfig($type . '_bus')
//            ->children()
//                ->append($this->getServiceBusNode($type, $routerClass))
//            ->end()
        ;
    }

    /**
     * Return the service bus node
     *
     * @return ArrayNodeDefinition
     */
    private function getServiceBusNode($type, $routerClass)
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root($type . '_buses');

        $node->beforeNormalization()
            ->ifTrue
            (
            // check for XML config
                function ($v) {
                    $bus = 'command';
                    return !isset($v[$bus . 'es']) && isset($v[$bus]) && is_array($v[$bus]);
                }
            )
            ->then(function ($v) {

                $bus = 'command';
                // normalize XML config
                $command_bus = [];
                foreach ($v[$bus] as $key => $value) {
                    $command_bus[$key] = $v[$bus][$key];
                }
                unset($v[$bus]);
                $v[$bus . 'es'] = $command_bus;

                return $v;
            })
            ->end();

        /** @var $serviceBusNode ArrayNodeDefinition */
        $serviceBusNode = $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
        ;

        $serviceBusNode
            ->fixXmlConfig('plugin', 'plugins')
            ->children()
                ->scalarNode('message_factory')->defaultValue(MessageFactory::class)->end()
                ->arrayNode('plugins')
                    ->beforeNormalization()
                        // fix single node in XML
                        ->ifString()->then(function ($v) { return [$v];})
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('router')
                    ->fixXmlConfig('route', 'routes')
                    ->children()
                        ->scalarNode('type')->defaultValue($routerClass)->end()
                        ->arrayNode('routes')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) {
                                        return array('class' => $v);
                                    })
                                ->end()
                                ->children()
                                    ->scalarNode('class')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
