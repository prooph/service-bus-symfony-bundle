<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2017 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus;

use Prooph\ServiceBus\MessageBus;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MessageBusFactory
{
    public function create(string $class, ContainerInterface $container, array $plugins = []) : MessageBus
    {
        /** @var MessageBus $bus */
        $bus = new $class();

        foreach ($plugins as $pluginId) {
            $plugin = $container->get($pluginId);
            $plugin->attachToMessageBus($bus);
        }

        return $bus;
    }
}
