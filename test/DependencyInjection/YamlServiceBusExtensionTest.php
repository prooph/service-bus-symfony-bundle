<?php
declare(strict_types = 1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class YamlServiceBusExtensionTest extends AbtractServiceBusExtensionTestCase
{
    protected function loadFromFile(ContainerBuilder $container, $file)
    {
        $loadYaml = new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixture/config/yml'));
        $loadYaml->load($file.'.yml');
    }
}
