<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\ServiceBus\Container;

use Interop\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainer;

class SymfonyContainerWrapper implements ContainerInterface
{
    /**
     * @var SymfonyContainer
     */
    private $innerContainer;

    public function __construct(SymfonyContainer $container)
    {
        $this->innerContainer = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->innerContainer->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->innerContainer->has($id);
    }
}
