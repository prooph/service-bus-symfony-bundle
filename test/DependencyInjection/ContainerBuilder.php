<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/service-bus-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2017 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/service-bus-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\ServiceBus\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/** @internal */
class ContainerBuilder
{
    /** @var FileLoader */
    private $fileLoaderFactory;

    /** @var string */
    private $fileExtension;

    /** @var array[] */
    private $parameters;

    /** @var ExtensionInterface[] */
    private $extensions = [];

    /** @var CompilerPassInterface[] */
    private $compilerPasses = [];

    /** @var string[] */
    private $configFiles = [];

    public static function buildContainer(callable $fileLoaderFactory, string $fileExtension): self
    {
        return new self($fileLoaderFactory, $fileExtension);
    }

    private function __construct(callable $fileLoaderFactory, string $fileExtension)
    {
        $this->fileLoaderFactory = $fileLoaderFactory;
        $this->fileExtension = $fileExtension;
        $this->parameters = [
            'kernel.debug' => false,
            'kernel.bundles' => [],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../src',
        ];
    }

    public function withParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    public function withExtensions(ExtensionInterface ...$extensions): self
    {
        $this->extensions = array_merge($this->extensions, $extensions);

        return $this;
    }

    public function withCompilerPasses(CompilerPassInterface ...$compilerPasses): self
    {
        $this->compilerPasses = array_merge($this->compilerPasses, $compilerPasses);

        return $this;
    }

    public function withConfigFiles(string ...$fileNames): self
    {
        $this->configFiles = array_merge($this->configFiles, $fileNames);

        return $this;
    }

    public function compile(): SymfonyContainerBuilder
    {
        $container = new SymfonyContainerBuilder(new ParameterBag($this->parameters));
        array_walk($this->extensions, [$container, 'registerExtension']);

        $fileLoader = call_user_func($this->fileLoaderFactory, $container);
        array_walk($this->configFiles, function (string $fileName) use ($fileLoader) {
            $fileLoader->load("$fileName.{$this->fileExtension}");
        });

        // array_walk is impossible here because the key will be passed as second parameter
        array_map([$container, 'addCompilerPass'], $this->compilerPasses);

        if (class_exists(ResolveChildDefinitionsPass::class)) {
            $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        } else {
            $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveDefinitionTemplatesPass()]);
        }
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
