# WPTechnix DI

[![Packagist Version](https://img.shields.io/packagist/v/wptechnix/di-container)](https://packagist.org/packages/wptechnix/di-container)
![PHP 8.0+](https://img.shields.io/badge/php-^8.0-8892bf)
![PSR-11](https://img.shields.io/badge/PSR--11-✓-brightgreen)
![MIT](https://img.shields.io/badge/license-MIT-blue)

A minimal, explicit PSR-11 dependency injection container for PHP.  
No autowiring, no reflection, no magic — what you register is exactly what you get.

## Installation

```bash
composer require wptechnix/di
```

## Quick Start

### 1. Create the container

```php
use WPTechnix\DI\Container;

$container = new Container();
```

### 2. Register a singleton (shared service)

Singletons are created once and cached. Perfect for stateless services like loggers, database connections, or configuration readers.

```php
use Psr\Log\LoggerInterface;
use App\Logging\FileLogger;

$container->singleton(LoggerInterface::class, FileLogger::class);
```

Now every time you ask for `LoggerInterface::class` you get the same `FileLogger` instance.

### 3. Register a factory (new instance each time)

Use factories when each resolution needs a fresh object, for example a mailer that picks up current settings, a report generator that depends on the current user, or any stateful helper.

```php
use App\Reporting\ReportGenerator;
use App\Context\CurrentUser;

$container->factory(ReportGenerator::class, function (Container $c) {
    return new ReportGenerator($c->get(CurrentUser::class));
});
```

Every call to `$container->get(ReportGenerator::class)` gives you a brand new `ReportGenerator`.

### 4. Resolve services

```php
$logger = $container->get(LoggerInterface::class);   // FileLogger instance (cached)
$report = $container->get(ReportGenerator::class);    // Fresh ReportGenerator each time
```

Check if a service exists before resolving:

```php
if ($container->has(LoggerInterface::class)) {
    // ...
}
```

## More Features

- **Aliases** — point one identifier to another.  
  ```php
  $container->alias('log', LoggerInterface::class);
  $container->get('log'); // same FileLogger instance
  ```

- **Constructor parameters** — pass primitive values or other services fluently.  
- **Tags** — group related services and resolve them all at once.  
- **Extenders** — decorate a service before its first resolution.  
- **Service providers** — two‑phase bootstrapping for large applications.

See the **[Getting Started](docs/Home.md)** guide for a full walkthrough, or browse the other docs:

- [Core Concepts](docs/Core-Concepts.md)
- [Constructor Parameters](docs/Constructor-Parameters.md)
- [Tagging](docs/Tagging.md)
- [Extending Services](docs/Extending-Services.md)
- [Service Providers](docs/Service-Providers.md)
- [API Reference](docs/API-Reference.md)

## Requirements

- PHP 8.0 or later
- `psr/container` ^1.1 || ^2.0

## License

MIT
