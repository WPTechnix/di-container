# Passing Constructor Parameters

Not every service can be instantiated with zero arguments.  
You can pass constructor arguments fluently using `addParameter()` and `addParameters()`.

## Passing a Primitive Value

Use `addParameter()` to pass a plain value like a string, integer, or array.

```php
use App\Cache\RedisCache;

$container->singleton(RedisCache::class)
    ->addParameter('tcp://127.0.0.1:6379');
```

The value is passed directly to the constructor in the order you add it.

## Passing a Service Dependency

When a constructor argument is another service, set the `resolve` flag to `true`.  
The container will fetch that service for you at resolution time.

```php
use Psr\Log\LoggerInterface;
use App\Cache\RedisCache;
use App\Logging\FileLogger;

$container->singleton(LoggerInterface::class, FileLogger::class);

$container->singleton(RedisCache::class)
    ->addParameter('tcp://127.0.0.1:6379')               // host (string)
    ->addParameter(LoggerInterface::class, resolve: true); // logger (service)
```

When `RedisCache` is resolved, the container calls `$container->get(LoggerInterface::class)` for the second argument.

## Passing a Closure

You can also pass a Closure with `resolve: true`. The closure receives the container and returns the value.

```php
$container->factory(ReportGenerator::class)
    ->addParameter(function (Container $c) {
        $config = $c->get(Config::class);
        $timezone = new \DateTimeZone($config->get('timezone'));
        return new \DateTime('now', $timezone);
    }, resolve: true);
```

Useful for dynamic values that aren't services.

## All Parameters at Once

`addParameters()` provides **all** parameters with a plain list.  
**Important:** it does **not** support the `resolve` flag. Every value is used as-is.

```php
$container->singleton(RedisCache::class)
    ->addParameters(['tcp://127.0.0.1:6379', 6379]); // Each array item passed as a parameter.
```

If you need resolved dependencies alongside primitives, use `addParameter()` instead:

```php
$container->singleton(RedisCache::class)
    ->addParameter('tcp://127.0.0.1:6379')
    ->addParameter(LoggerInterface::class, resolve: true);
```

## Order Matters

Parameters are stored in the order you add them. They'll be spread into the constructor in that exact sequence.

```php
class Mailer
{
    public function __construct(
        string $host,
        int $port,
        LoggerInterface $logger,
    ) {}
}

$container->factory(Mailer::class)
    ->addParameter('smtp.example.com')
    ->addParameter(587)
    ->addParameter(LoggerInterface::class, resolve: true);
```

## Parameters with Callable Factories

When you use a **callable factory** instead of a class name, the resolved parameters are **not** auto-injected into the constructor. They are passed as the second argument array to your callable — your factory must use them explicitly.

Compare the two styles:

```php
// String factory — parameters are automatically spread into the constructor
$container->singleton(Mailer::class, SmtpMailer::class)
    ->addParameter('smtp.example.com')
    ->addParameter(587);

// Callable factory — parameters arrive as an array; you spread them yourself
$container->singleton(Mailer::class, function (Container $c, array $params) {
    return new SmtpMailer(...$params);
})->addParameter('smtp.example.com')
  ->addParameter(587);
```

Both produce the same result. The callable factory gives you full control — you can ignore, modify, or conditionally apply parameters before constructing the service.

## Full Example: Cache with Logger

```php
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use App\Cache\RedisCache;
use App\Logging\FileLogger;

$container->singleton(LoggerInterface::class, FileLogger::class);

$container->singleton(CacheInterface::class, RedisCache::class)
    ->tag('cache')
    ->addParameter('tcp://127.0.0.1:6379')
    ->addParameter(LoggerInterface::class, resolve: true);

$cache = $container->get(CacheInterface::class);
// RedisCache('tcp://127.0.0.1:6379', FileLogger instance)
```

## What If Parameters Are Missing?

If you register a class with required constructor parameters but forget to provide them, the container cannot warn you at registration time. At resolution time, PHP throws a `TypeError` when it tries to instantiate the class with too few arguments.

Always ensure your parameter count matches the constructor signature when using a string or null factory.

## Summary

- Use `addParameter($value)` for plain values.
- Use `addParameter($serviceId, resolve: true)` for other services.
- Use `addParameter($closure, resolve: true)` for dynamic values.
- `addParameters()` sets all parameters but never resolves — stick to `addParameter()` when you need mixed resolved and plain values.
- Parameters are passed to the constructor in the order they were added.
- With callable factories, parameters arrive as a second argument array — your factory must handle them explicitly.

---

**Previous:** [Core Concepts](Core-Concepts.md) · **Next:** [Grouping Services with Tags](Tagging.md)
