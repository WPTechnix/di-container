# Core Concepts

Understanding a few key ideas will help you get the most out of the container.

## Identifiers

A service identifier is any string, but the convention is to use `ClassName::class` or `InterfaceName::class`.  
This keeps your code type-safe, avoids typos, and makes it obvious what you're resolving.

```php
use Psr\Log\LoggerInterface;

$container->singleton(LoggerInterface::class, FileLogger::class);
$container->get(LoggerInterface::class); // FileLogger
```

You may still use plain strings for shorthand, for example `'log'`.

## Singleton vs Factory

**Singleton** — created once, cached forever.  
Best for:

- Stateless services (loggers, serializers, HTTP clients).
- Shared resources (database connections, cache pools).
- Configuration objects.
- Anything that behaves identically no matter how many times you call it.

**Factory** — created fresh on every `get()`.  
Best for:

- Services that depend on current request state (a mailer that reads from request-specific config).
- Objects carrying temporary user data (a cart, a form processor).
- Short-lived helpers you don't want to reuse between tasks.

Rule of thumb: if the service has no mutable per-request state, make it a singleton.  
If it must be new every time, use a factory.

## Definitions

When you call `singleton()` or `factory()`, you get back a `Definition` object.  
It's a fluent interface that lets you:

- Attach **tags** — `->tag('console.command')`
- Add **constructor parameters** — `->addParameter('localhost')` or `->addParameter(OtherService::class, resolve: true)`

```php
$container->singleton(CacheInterface::class, RedisCache::class)
    ->tag('backend.cache')
    ->addParameter('tcp://127.0.0.1:6379');
```

## Overriding Definitions

The `$override` flag on `singleton()` and `factory()` replaces an existing definition for the same identifier.

A practical use case is swapping a real service for a fake during testing:

```php
// Application bootstrap
$container->singleton(MailerInterface::class, SmtpMailer::class);
$container->singleton(LoggerInterface::class, FileLogger::class);

// Test — replace the mailer with a fake
$container->singleton(MailerInterface::class, FakeMailer::class, override: true);

// The logger is still the same, but the mailer is now FakeMailer
$mailer = $container->get(MailerInterface::class); // FakeMailer instance
```

Without `override: true`, a duplicate identifier throws `DuplicateServiceException`. Override also removes any alias that shared the identifier, so the new definition takes full precedence.

## Tags

Tags let you group services and resolve them as a collection.

```php
$container->singleton(FirstCommand::class)->tag('console.command');
$container->singleton(SecondCommand::class)->tag('console.command');

$commands = $container->tagged('console.command'); // list of resolved instances
```

Use cases: CLI commands, event subscribers, middleware pipes — anything you want to collect and iterate over.

See the [Tagging](Tagging.md) guide for more details.

## Extenders

An extender allows you to **decorate** a service before its first resolution.  
Perfect for wrapping a logger with a processor, or adding common behaviour to many services without changing their registration.

```php
$container->extend(LoggerInterface::class, function ($logger, Container $c) {
    return new BufferingLogger($logger);
});
```

Extenders run in registration order. You must register them before the service is resolved; singletons that have already been resolved cannot be extended.

The [Extending Services](Extending-Services.md) guide covers this in depth.

## Service Providers

As your application grows, registering dozens of services in a single bootstrap file becomes messy.  
Service providers split registrations into cohesive classes, each containing a `register()` and a `boot()` method.

- `register()` — bind services; never resolve anything.
- `boot()` — wire things together; safe to resolve any registered service.

All providers are registered first, then booted. That guarantees the boot phase can depend on every service.

```php
class LoggerServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(LoggerInterface::class, FileLogger::class);
    }

    public function boot(Container $container): void
    {
        // Already available: $container->get(LoggerInterface::class)
    }
}
```

Read the [Service Providers](Service-Providers.md) guide for more.

---

**Previous:** [Getting Started](Home.md) · **Next:** [Passing Constructor Parameters](Constructor-Parameters.md)
