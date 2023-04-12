<?php

use Vscode\PhpIoc\Container;

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';

// TODO: add to dependencies maybe
(new \Whoops\Run())
  ->pushHandler(new \Whoops\Handler\PrettyPageHandler())
  ->register();

// $container = Container::getInstance();

interface ClassInterface
{
}

class SomeClass implements ClassInterface
{
}

class OtherClass implements ClassInterface
{
  public function __construct(
    public ClassInterface $someClass,
  ) {
  }
}

interface DatabaseInterface
{
}

class Database implements DatabaseInterface
{
}

$container = (new Container())
  ->singleton(DatabaseInterface::class, Database::class)
  ->singleton(Database::class, Database::class)
  ->singleton('database', fn () => new Database())
  ->factory(ClassInterface::class, SomeClass::class)
  ->factory(ClassInterface::class, fn (Container $container) => new OtherClass($container->get(SomeClass::class)));

$otherClass = $container->get(OtherClass::class);
$database = $container->get(Database::class);

dump($container);
dump($database === $container->get(DatabaseInterface::class)); // or use spaceship operator <=>
