<?php

namespace Vscode\PhpIoc;

use Closure;
use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
// use Vscode\PhpIoc\Traits\Singleton;

class Container implements ContainerInterface
{
  // use Singleton;

  /** @var array<string, Closure|string> */
  private array $entries = [];

  /** @var array<string, Closure|string> */
  private array $singletonEntries = [];

  public function singleton(string $id, Closure|string $concrete): self
  {
    $this->singletonEntries[$id] = $concrete;

    return $this;
  }

  /**
   * Get an entry from the container by ID.
   *
   * @template T
   * @param class-string<T>|string $id
   * @psalm-param class-string<T> $id
   * @return T
   * @throws Exception if the entry cannot be resolved
   */
  public function get(string $id): string|null|object
  {
    if (!$this->has($id)) {
      return $this->resolve($id);
    }

    $entry = $this->entries[$id];

    if ($entry instanceof Closure) {
      return $entry($this);
    }

    return $this->resolve($entry);
  }

  public function has(string $id): bool
  {
    return isset($this->entries[$id]);
  }

  public function factory(string $id, Closure|string $concrete): self
  {
    $this->entries[$id] = $concrete;

    return $this;
  }

  /**
   * Resolve a class or interface from the container.
   *
   * @template T
   * @param class-string<T> $dependency
   * @psalm-param class-string<T> $dependency
   * @return T
   * @throws Exception if the dependency cannot be resolved
   */
  private function resolve(string $dependency): string|null|object
  {
    if (isset($this->singletonEntries[$dependency])) {
      return $this->singletonEntries[$dependency];
    }

    $reflectionClass = new ReflectionClass($dependency);

    if (!$reflectionClass->isInstantiable()) {
      throw new Exception('Class "' . $dependency . '" is not instantiable');
    }

    $constructor = $reflectionClass->getConstructor();

    if (!$constructor) {
      return new $dependency();
    }

    $parameters = $constructor->getParameters();

    if (!$parameters) {
      return new $dependency();
    }

    $dependencies = array_map([$this, 'resolveDependency'], $parameters);
    $instance = $reflectionClass->newInstanceArgs($dependencies);

    return $instance;
  }

  /**
   * Resolve a single dependency for a constructor parameter.
   *
   * @param ReflectionParameter $parameter
   * @return mixed
   * @throws Exception if the dependency cannot be resolved
   */
  private function resolveDependency(ReflectionParameter $parameter): mixed
  {
    $name = $parameter->getName();
    $type = $parameter->getType();

    if (!$type) {
      throw new Exception("Failed to resolve class because parameter ({$name}) is missing a type hint");
    }

    if ($type instanceof ReflectionUnionType) {
      throw new Exception("Failed to resolve because of union type for parameter ({$name})");
    }

    if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
      $dependency = $type->getName();

      return $this->get($dependency);
    }

    throw new Exception("Failed to resolve class because invalid parameter ({$name})");
  }
}
