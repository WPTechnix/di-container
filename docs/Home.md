# Getting Started

This guide walks you through the first steps with the container.  
You'll learn how to create a container, register services as singletons or factories, resolve them, and use aliases.

## 1. Create the Container

```php
use WPTechnix\DI\Container;

$container = new Container();
```

No configuration files, no service providers yet — just an empty container ready for manual registration.

## 2. Register a Singleton

A **singleton** is created once and then cached.  
Any class that is stateless or manages a shared resource is a good candidate.  
Common examples: loggers, database connections, cache pools, event dispatchers, HTTP clients.

Bind an interface to a concrete class:

```php
use Psr\Log\LoggerInterface;
use App\Logging\FileLogger;

$container->singleton(LoggerInterface::class, FileLogger::class);
```

When you resolve `LoggerInterface::class`, you always get the same `FileLogger` object.

```php
$logger = $container->get(LoggerInterface::class);
```

You can also omit the second argument. The container then assumes the identifier itself is the class name:

```php
$container->singleton(FileLogger::class); // resolves to FileLogger
```

## 3. Register a Factory

A **factory** creates a new instance on every `get()` call.  
Use factories when a service depends on runtime values, holds temporary state, or must never be reused across different requests or contexts.

A few realistic use cases:

- A mailer that uses settings which might change between emails.
- A report builder that needs the current user.
- A form handler that populates data per request.

```php
use App\Reporting\ReportGenerator;
use App\Context\CurrentUser;

$container->factory(ReportGenerator::class, function (Container $c) {
    return new ReportGenerator($c->get(CurrentUser::class));
});
```

Every call to `$container->get(ReportGenerator::class)` gives a fresh `ReportGenerator`.

You can also just provide a class name, just like with singletons:

```php
$container->factory(SomeHelper::class);
```

## 4. Resolve Services

```php
$logger = $container->get(LoggerInterface::class);
$reportGenerator = $container->get(ReportGenerator::class);
```

Before resolving a service you can check if it's registered with `has()`:

```php
if ($container->has(LoggerInterface::class)) {
    $logger = $container->get(LoggerInterface::class);
}
```

`has()` returns `false` for unknown identifiers and for aliases whose target doesn't exist.

## 5. Use Aliases

An alias lets you resolve the same service with a different name.  
Perfect for backward compatibility or shorthand identifiers.

```php
$container->alias('logger', LoggerInterface::class);

$logger = $container->get('logger'); // same FileLogger instance
```

## 6. Passing Constructor Parameters

When your service expects primitive values or other services, use `addParameter()`.

```php
use App\Cache\RedisCache;

$container->singleton(RedisCache::class)
    ->addParameter('tcp://127.0.0.1:6379'); // host
```

For service dependencies, set the `resolve` flag to `true`:

```php
use App\Cache\CacheInterface;
use App\Serialization\SerializerInterface;

$container->singleton(CacheInterface::class, RedisCache::class)
    ->addParameter('tcp://127.0.0.1:6379')
    ->addParameter(SerializerInterface::class, resolve: true);
```

Now the second argument will be the resolved `SerializerInterface` service.

## 7. What's Next?

You've now registered and resolved basic services. The container can do much more:

- **[Core Concepts](Core-Concepts.md)** — singleton vs. factory, definitions, extenders, providers.
- **[Constructor Parameters](Constructor-Parameters.md)** — pass primitives, services, or closures to constructors.
- **[Tagging](Tagging.md)** — group services and resolve them as a collection (e.g. all CLI commands).
- **[Extending Services](Extending-Services.md)** — decorate a service before its first use.
- **[Service Providers](Service-Providers.md)** — organise registrations into dedicated classes for large applications.
- **[API Reference](API-Reference.md)** — full method signatures and exceptions.

---

**Next:** [Core Concepts](Core-Concepts.md)
