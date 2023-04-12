<?php

namespace Vscode\PhpIoc\Traits;

trait Singleton
{
  private static $instance;

  public static function getInstance(): static
  {
    return self::$instance ??= new static();
  }

  private function __construct()
  {
    // private constructor to prevent direct instantiation
  }

  private function __clone()
  {
    // private clone method to prevent cloning
  }
}
