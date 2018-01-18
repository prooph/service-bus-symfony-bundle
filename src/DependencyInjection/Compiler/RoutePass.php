<?php

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\DependencyInjection\Compiler;

use Prooph\Bundle\ServiceBus\DependencyInjection\ProophServiceBusExtension;
use Prooph\Bundle\ServiceBus\Exception\CompilerPassException;
use Prooph\Common\Messaging\HasMessageName;
use Prooph\Common\Messaging\Message;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RoutePass implements CompilerPassInterface
{
    private static function tryToDetectMessageName(ReflectionClass $messageReflection): ?string
    {
        if (! $messageReflection->implementsInterface(HasMessageName::class)) {
            return null;
        }
        $instance = $messageReflection->newInstanceWithoutConstructor(); /* @var $instance HasMessageName */
        if ($messageReflection->hasMethod('init')) {
            $init = $messageReflection->getMethod('init');
            $init->setAccessible(true);
            $init->invoke($instance);
        }

        return $instance->messageName();
    }

    public function process(ContainerBuilder $container)
    {
        foreach (ProophServiceBusExtension::AVAILABLE_BUSES as $type) {
            if (! $container->hasParameter('prooph_service_bus.' . $type . '_buses')) {
                continue;
            }

            $buses = $container->getParameter('prooph_service_bus.' . $type . '_buses');

            foreach ($buses as $name => $bus) {
                $router = $container->findDefinition(sprintf('prooph_service_bus.%s.router', $name));
                $routerArguments = $router->getArguments();
                /** @var string[] $handlers */
                $handlers = $routerArguments[0];
                foreach ($handlers as $eachHandlers) {
                    $eachHandlers = (array) $eachHandlers;
                    foreach ($eachHandlers as $eachHandler) {
                        if ($container->has($eachHandler)) {
                            $container->getDefinition($eachHandler)->setPublic(true);
                        }
                    }
                }

                $handlers = $container->findTaggedServiceIds(sprintf('prooph_service_bus.%s.route_target', $name));

                foreach ($handlers as $id => $args) {
                    $container->getDefinition($id)->setPublic(true);
                    // Safeguard to have only one tag per command / query
                    if ($type !== 'event' && count($args) > 1) {
                        throw CompilerPassException::tagCountExceeded($type, $id, $bus);
                    }
                    foreach ($args as $eachArgs) {
                        if ((! isset($eachArgs['message_detection']) || $eachArgs['message_detection'] !== true) && ! isset($eachArgs['message'])) {
                            throw CompilerPassException::messageTagMissing($id);
                        }

                        $messageNames = isset($eachArgs['message'])
                            ? [$eachArgs['message']]
                            : $this->recognizeMessageNames($container, $container->getDefinition($id), $id, $type);

                        if ($type === 'event') {
                            $routerArguments[0] = array_merge_recursive(
                                $routerArguments[0],
                                array_combine($messageNames, array_fill(0, count($messageNames), [$id]))
                            );
                            $routerArguments[0] = array_map('array_unique', $routerArguments[0]);
                        } else {
                            $routerArguments[0] = array_merge(
                                $routerArguments[0],
                                array_combine($messageNames, array_fill(0, count($messageNames), $id))
                            );
                        }
                    }
                }
                $router->setArguments($routerArguments);

                // Update route configuration parameter
                $configId = sprintf('prooph_service_bus.%s.configuration', $name);

                $routes = is_array($routerArguments[0]) ? $routerArguments[0] : [];
                $config = array_replace($container->getParameter($configId), ['router' => ['routes' => $routes]]);

                $container->setParameter($configId, $config);
            }
        }
    }

    private function recognizeMessageNames(
        ContainerBuilder $container,
        Definition $routeDefinition,
        string $routeId,
        string $busType
    ): array {
        /** @var string $routeClass Help phpstan */
        $routeClass = $routeDefinition->getClass();
        $handlerReflection = $container->getReflectionClass($routeClass);
        if (! $handlerReflection) {
            throw CompilerPassException::unknownHandlerClass($routeClass, $routeId, $busType);
        }

        $methodsWithMessageParameter = array_filter(
            $handlerReflection->getMethods(ReflectionMethod::IS_PUBLIC),
            function (ReflectionMethod $method) {
                return ($method->getNumberOfRequiredParameters() === 1 || $method->getNumberOfRequiredParameters() === 2)
                    && $method->getParameters()[0]->getClass()
                    && $method->getParameters()[0]->getClass()->getName() !== Message::class
                    && $method->getParameters()[0]->getClass()->implementsInterface(Message::class);
            }
        );

        return array_filter(array_unique(array_map(function (ReflectionMethod $method) {
            return self::tryToDetectMessageName($method->getParameters()[0]->getClass());
        }, $methodsWithMessageParameter)));
    }
}
