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

        $this->addServiceBusSections('command',  CommandRouter::class, $rootNode);
        $this->addServiceBusSections('event',  EventRouter::class, $rootNode);
        $this->addServiceBusSections('query',  QueryRouter::class, $rootNode);

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
        $treeBuilder = new TreeBuilder();
        $routesNode = $treeBuilder->root('routes');

        /** @var $routesNode ArrayNodeDefinition */
        $listenerNode = $routesNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey($type)
            ->prototype('event' === $type ? 'array' : 'scalar')
        ;

        if ('event' === $type) {
            $listenerNode
                    ->beforeNormalization()
                        ->ifTrue(function ($v) {
                            // XML uses listener nodes
                            return isset($v['listener']);
                        })
                        ->then(function ($v) {
                            // fix single node in XML
                            return (array)$v['listener'];
                        })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
            ;
        }

        $node
            ->fixXmlConfig($type . '_bus', $type . '_buses')
            ->children()
            ->arrayNode($type . '_buses')
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
                            ->append($routesNode)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
