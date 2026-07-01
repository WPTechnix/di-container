# Service Providers

As your application grows, registering every service in a single bootstrap file becomes hard to maintain.  
Service providers give you a structured way to group related registrations.

## What Is a Service Provider?

A service provider is a class that implements `WPTechnix\DI\ServiceProvider`.  
It has two methods:

- **`register(Container $container): void`** — bind services and aliases. Do **not** resolve anything here.
- **`boot(Container $container): void`** — wire things together. Safe to resolve any service because all providers have already been registered.

## Two-Phase Boot Sequence

When you call `$container->boot()`, the container processes all queued providers in two strict phases:

1. **Registration phase** — calls `register()` on every provider, in the order they were added.
2. **Boot phase** — calls `boot()` on every provider, again in the same order.

This guarantees that by the time `boot()` runs, every service registered by any provider is available.

```php
$container->provider(new LoggerServiceProvider());
$container->provider(new RoutingServiceProvider());
$container->boot();
// All register() methods have run, now all boot() methods run.
```

## Creating a Provider

Imagine your application needs a PSR-3 logger and a mailer that depends on some configuration.

```php
use WPTechnix\DI\Container;
use WPTechnix\DI\ServiceProvider;
use Psr\Log\LoggerInterface;
use App\Logging\FileLogger;
use App\Mail\Mailer;
use App\Mail\MailerInterface;
use App\Configuration\MailConfig;

class LoggerServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(LoggerInterface::class, FileLogger::class);
    }

    public function boot(Container $container): void
    {
        // Ready to use the logger if needed, but not required here.
    }
}

class MailServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(MailConfig::class);

        $container->factory(MailerInterface::class, function (Container $c) {
            return new Mailer($c->get(MailConfig::class));
        });
    }

    public function boot(Container $container): void
    {
        // All registrations are done — we can safely fetch the mailer if we want.
        // For example, attach it to an event system:
        // $container->get(EventDispatcher::class)->addSubscriber($container->get(MailerInterface::class));
    }
}
```

Then wire them up:

```php
$container = new Container();
$container->provider(new LoggerServiceProvider());
$container->provider(new MailServiceProvider());
$container->boot();

$logger = $container->get(LoggerInterface::class);
$mailer = $container->get(MailerInterface::class);
```

## Register vs. Boot

- **`register()`** is for **bindings** — `singleton()`, `factory()`, `alias()`.  
  Do not call `$container->get()` inside `register()`. The service you need may not exist yet.

- **`boot()`** is for **integration** — resolving services and connecting them together.  
  By now every provider has registered its services, so it's safe to resolve anything.

Example: in `register()` you bind a repository interface, and in `boot()` you preload some cache using that repository.

## Chaining Multiple Providers

Providers can be chained fluently for a clean bootstrap file:

```php
$container
    ->provider(new LoggerServiceProvider())
    ->provider(new MailServiceProvider())
    ->provider(new RoutingServiceProvider())
    ->boot();
```

## Freezing

Once `boot()` is called, the container is **frozen**.  
You **cannot** add new providers, and you cannot call `boot()` again.

```php
$container->boot();
$container->provider(new SomeOtherProvider()); // throws FrozenContainerException
$container->boot();                            // throws FrozenContainerException
```

This intentional restriction enforces a clear bootstrap phase and prevents accidental late registrations.

> **Note:** `singleton()` and `factory()` still accept new registrations after boot, so the container is not fully sealed. However, registering services post-boot makes the bootstrap flow harder to reason about. Best practice is to complete all registrations before calling `boot()`.

## When Not to Use Providers

For very small applications or scripts, you can skip providers altogether and just call `singleton()` / `factory()` directly.  
Providers become useful once you have more than a handful of services that naturally group together — for example, a `DatabaseServiceProvider`, an `HttpServiceProvider`, or a `ConsoleServiceProvider`.

## Summary

- Implement `ServiceProvider` and put bindings in `register()`, wiring in `boot()`.
- Queue providers with `->provider()`.
- Call `$container->boot()` exactly once to trigger the two-phase process.
- After boot, the container is frozen — no more providers can be added.

---

**Previous:** [Decorating Services with Extenders](Extending-Services.md) · **Next:** [API Reference](API-Reference.md)
