# Decorating Services with Extenders

Extenders let you modify a service **before** it is resolved for the first time.  
Think of them as decorators applied at the container level — you can wrap, replace, or enhance any registered service without touching its original definition.

## When to Use Extenders

- Add a prefix to all log messages.
- Log every email that gets sent.
- Measure how long cache operations take.
- Apply any cross-cutting concern without changing the original class.

The key rule: you must register extenders **before** the service is first resolved.  
For singletons, once `get()` is called, the instance is frozen and no further extenders can be added.

## Extender Signature

An extender is a callable with the following signature:

```
callable(mixed $instance, Container $c): mixed
```

- `$instance` — the current instance (the original object or the result of a previous extender).
- `$c` — the container, allowing you to resolve any dependencies your decorator needs.
- Must return the decorated instance (or a completely new object).

## Basic Example: Add a Log Prefix

You have a standard logger and want every message to include an application prefix.

```php
use Psr\Log\LoggerInterface;
use App\Logging\FileLogger;

$container->singleton(LoggerInterface::class, FileLogger::class);
```

Register an extender that wraps the logger:

```php
use App\Logging\PrefixLogger;

$container->extend(LoggerInterface::class, function (LoggerInterface $logger, Container $c) {
    return new PrefixLogger($logger, '[MyApp] ');
});
```

Now every log entry gets the prefix:

```php
$logger = $container->get(LoggerInterface::class);
$logger->info('User registered');  // [MyApp] User registered
```

The original `FileLogger` never knows about the prefix — the decorator handles it.

## Example: Log Every Email

You have a mailer service. You want to keep a simple log of every email sent, for debugging.

```php
use App\Mail\MailerInterface;
use App\Mail\SmtpMailer;

$container->singleton(MailerInterface::class, SmtpMailer::class);
```

Extend it to log before sending:

```php
use App\Mail\LoggingMailer;
use Psr\Log\LoggerInterface;

$container->extend(MailerInterface::class, function (MailerInterface $mailer, Container $c) {
    $logger = $c->get(LoggerInterface::class);
    return new LoggingMailer($mailer, $logger);
});
```

Every call to `send()` on the mailer now also writes a log entry.  
The rest of your application still works with `MailerInterface` — no change needed.

## Example: Measure Cache Timing

You have a simple cache. You want to see how long each `get()` operation takes, without altering the caching logic.

```php
use Psr\SimpleCache\CacheInterface;
use App\Cache\FileCache;

$container->singleton(CacheInterface::class, FileCache::class);
```

Extend with a timing decorator:

```php
use App\Cache\TimedCache;
use Psr\Log\LoggerInterface;

$container->extend(CacheInterface::class, function (CacheInterface $cache, Container $c) {
    $logger = $c->get(LoggerInterface::class);
    return new TimedCache($cache, $logger);
});
```

Now every cache call logs its duration — great for spotting slow operations.

## Extender Order

Extenders run in the order they are registered. You can stack multiple decorators:

```php
$container->extend(LoggerInterface::class, function ($logger, $c) {
    return new PrefixLogger($logger, '[MyApp] ');
});

$container->extend(LoggerInterface::class, function ($logger, $c) {
    return new JsonFormatterLogger($logger);
});
```

Final result: `JsonFormatterLogger` wraps `PrefixLogger` wraps the original logger.

## Using the Container Inside an Extender

The container is passed as the second argument. Use it to fetch any service your decorator depends on.

```php
$container->extend(MailerInterface::class, function (MailerInterface $mailer, Container $c) {
    $logger = $c->get(LoggerInterface::class);
    return new LoggingMailer($mailer, $logger);
});
```

This is safe because extenders only run at resolution time, after all services are registered.

## Restrictions

- **Singleton already resolved**: If a shared service has been retrieved at least once, you cannot extend it.  
  The container will throw `ServiceAlreadyResolvedException`.

  ```php
  $container->get(LoggerInterface::class);
  $container->extend(LoggerInterface::class, ...); // throws exception
  ```

- **Factory services**: You can extend factories too. The extender runs on **every** resolution, since each call to `get()` creates a fresh instance.

  ```php
  $container->factory(ReportGenerator::class, ...);
  $container->extend(ReportGenerator::class, function ($report, $c) {
      return new TimedReportGenerator($report);
  });

  $a = $container->get(ReportGenerator::class); // decorated fresh instance
  $b = $container->get(ReportGenerator::class); // another decorated fresh instance
  ```

- The extender callable is never cached — it runs each time the service is resolved (once for singletons, every time for factories).

## Practical Use Case: Mask Sensitive Data in Logs

Your application logs request data, but you need to remove passwords before they hit the log file.

```php
use Psr\Log\LoggerInterface;
use App\Logging\MaskingLogger;

$container->extend(LoggerInterface::class, function (LoggerInterface $logger, Container $c) {
    return new MaskingLogger($logger, ['password', 'token']);
});
```

All log calls pass through `MaskingLogger`, which scrubs the listed fields.  
Your logging code remains exactly the same — pure decorator pattern.

## Summary

- Call `$container->extend()` to decorate a service before it is first resolved.
- The extender receives the current instance and the container; it must return the decorated instance.
- Use extenders for common needs: logging, timing, prefixing, masking.
- Register extenders before the first `get()` on a singleton.
- Extenders run in registration order and can be stacked.
- Factories are re-decorated on every resolution.

---

**Previous:** [Grouping Services with Tags](Tagging.md) · **Next:** [Service Providers](Service-Providers.md)
