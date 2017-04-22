<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\DependencyInjection\Compiler;

use Prooph\Bundle\ServiceBus\DependencyInjection\ProophServiceBusExtension;
use Prooph\ServiceBus\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RoutePass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $routesFromTaggedServices = [];

    public function process(ContainerBuilder $container)
    {
        foreach (ProophServiceBusExtension::AVAILABLE_BUSES as $type) {
            if (!$container->hasParameter('prooph_service_bus.'.$type.'_buses')) {
                continue;
            }

            $buses = $container->getParameter('prooph_service_bus.'.$type.'_buses');

            foreach ($buses as $name => $bus) {
                $this->routesFromTaggedServices[$name] = [];
                // Get existing routes from bus config
                $router = $container->findDefinition(sprintf('prooph_service_bus.%s.router', $name));
                $routerArguments = $router->getArguments();

                //
                foreach ($routerArguments[0] as $message => $routeTarget) {
                    // If we have an eventbus, multiple route targets are possible. Only unique route targets will be added to the message
                    if (is_array($routeTarget) && $type === 'event') {
                        array_map(function ($routeTarget) use ($type, $message, $name) {
                            $this->registerRoute($type, $message, $routeTarget, $name);
                        }, array_unique($routeTarget));
                    } else {
                        $this->registerRoute($type, $message, $routeTarget, $name);
                    }

                }

                $routeTargetId = sprintf('prooph_service_bus.%s.route_target', $name);
                $routeTargetServices = $container->findTaggedServiceIds($routeTargetId);

                foreach ($routeTargetServices as $routeTarget => $routeTags) {
                    // Guard: for command and query, only one tag is allowed per handler (1:1)
                    if ($type !== 'event' && count($routeTags) > 1) {
                        throw new RuntimeException(
                            sprintf(
                                'More than 1 %s handler tagged on service "%s" with tag "%s". Only events can have multiple handlers',
                                $type, $routeTarget, $routeTargetId
                            )
                        );
                    }

                    foreach ($routeTags as $tag) {
                        if (isset($tag['message']) && !empty($tag['message'])) {
                            $this->registerRoute($type, $tag['message'], $routeTarget, $name);

                            continue;
                        }
                        // Guard: throw an early exception on misconfiguration
                        throw new RuntimeException(sprintf('"message" tag key is missing or is empty on route target "%s"', $routeTarget));
                    }
                }
                $router->setArguments([$this->getRouteArgumentsForBus($name)]);
            }
        }
    }

    private function registerRoute(string $type, string $message, $routeTarget, string $busName): void
    {
        if ($type !== 'event' && is_string($routeTarget)) {
            // Disallow accidental overwriting of routes configured in the bus config with a tagged service
            if (isset($this->routesFromTaggedServices[$message])) {
                throw new RuntimeException(sprintf(
                    'Route target for %s "%s" is already mapped to "%s" in the config for "%s"', $type, $message, $routeTarget, $busName
                ));
            }
            $this->routesFromTaggedServices[$busName][$message] = $routeTarget;

            return;
        } elseif ($type === 'event' && is_string($routeTarget)) {
            // Silently don't add existing route targets
            $this->routesFromTaggedServices[$busName][$message][] = $routeTarget;
            $this->routesFromTaggedServices[$busName][$message] = array_unique($this->routesFromTaggedServices[$busName][$message]);

            return;
        }

        throw new RuntimeException(sprintf('Invalid config. Cannot route "%s" message "%s"', $type, $message));
    }

    private function getRouteArgumentsForBus(string $busName): array
    {
        return $this->routesFromTaggedServices[$busName];
    }
}
