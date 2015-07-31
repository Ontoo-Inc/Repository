<?php
namespace Ontoo\Repositories\Generators;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Ontoo\Repositories\Exceptions\FileExistsException;

/**
 * Class Generator
 *
 * @package Ontoo\Repositories\Generators
 */
abstract class Generator
{
    use AppNamespaceDetectorTrait;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $options;

    /**
     * Stub for generator.
     *
     * @var string
     */
    protected $stub;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->filesystem = new Filesystem;
    }

    public function getBasePath()
    {
        return config('repository.generator.basePath', app_path());
    }

    public function getRootNamespace()
    {
        return config('repository.generator.rootNamespace', $this->getAppNamespace());
    }

    public function getModelNamespace()
    {
        return config('repository.generator.modelNamespace', $this->getAppNamespace());
    }

    public function getNamespace()
    {
        $segments = $this->getSegments();
        array_pop($segments);
        $rootNamespace = $this->getRootNamespace();
        if ($rootNamespace == false) {
            return null;
        }

        return rtrim($rootNamespace . implode($segments, '\\'), '\\');
    }

    public function getReplacements()
    {
        return [
            'class' => $this->getClass(),
            'namespace' => $this->getNamespace(),
            'root_namespace' => $this->getRootNamespace()
        ];
    }

    public function run()
    {
        if ($this->filesystem->exists($path = $this->getPath())) {
            throw new FileExistsException($path);
        }
        if (!$this->filesystem->isDirectory($dir = dirname($path))) {
            $this->filesystem->makeDirectory($dir, 0777, true, true);
        }

        return $this->filesystem->put($path, $this->getStub());
    }

    protected function getName()
    {
        $name = $this->name;
        if (str_contains('\\', $this->name)) {
            $name = str_replace('\\', '/', $this->name);
        }

        return Str::studly(ucwords($name));
    }

    public function getPath()
    {
        return $this->getBasePath() . '/' . $this->getName() . '.php';
    }

    private function getClass()
    {
        return Str::studly(class_basename($this->getName()));
    }

    private function getSegments()
    {
        return explode('/', $this->getName());
    }

    private function getStub()
    {
        return new Stub(
            __DIR__ . '/stubs/' . $this->stub . '.stub',
            $this->getReplacements()
        );
    }

    public function getOption($key)
    {
        if (!$this->hasOption($key)) {
            return null;
        }

        return $this->options[$key] ?: null;
    }

    private function hasOption($key)
    {
        return array_key_exists($key, $this->options);
    }

    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        return $this->getOption($key);
    }
}
