# PSR-11 Dependency Injection Container for PHP 8.1+

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wptechnix/di-container.svg)](https://packagist.org/packages/wptechnix/di-container)
[![Tests](https://github.com/wptechnix/di-container/actions/workflows/tests.yml/badge.svg)](https://github.com/wptechnix/di-container/actions/workflows/tests.yml)
[![PHPStan](https://github.com/wptechnix/di-container/actions/workflows/phpstan.yml/badge.svg)](https://github.com/wptechnix/di-container/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wptechnix/di-container.svg)](https://packagist.org/packages/wptechnix/di-container)
[![Monthly Downloads](https://img.shields.io/packagist/dm/wptechnix/di-container.svg)](https://packagist.org/packages/wptechnix/di-container)
[![License](https://img.shields.io/github/license/wptechnix/di-container)](https://github.com/wptechnix/di-container/blob/main/LICENSE)

A lightweight, PSR-11 compliant dependency injection container for modern PHP applications, featuring autowiring, contextual bindings, attribute-based injection, and comprehensive service management. It provides strong type safety through PHP's type system and PHPStan integration, enabling excellent IDE autocompletion and runtime type checking. While universally applicable for any PHP project, it includes optimizations that make it particularly effective for WordPress plugin development.

## Key Features

* **PSR-11 Compliant** - Fully implements the [PHP-FIG PSR-11](https://www.php-fig.org/psr/psr-11/) container standard
* **Autowiring** - Automatic dependency resolution based on type hints
* **Constructor Injection** - Automatic dependency resolution through constructor parameters
* **Property Injection** - Inject dependencies using PHP 8 attributes on class properties
* **Method/Setter Injection** - Automatic dependency resolution through setter methods
* **Contextual Bindings** - Define specific implementations for different contexts
* **Service Providers** - Organize related bindings and bootstrapping logic
* **Service Tagging** - Group related services for collective resolution
* **Interface Binding** - Easily bind interfaces to implementations with full type safety
* **Type Safety** - Use interfaces and class names as service IDs for better type hints and IDE completion
* **Singleton & Factory Patterns** - Register services as shared instances or factories
* **Service Extension** - Decorate and extend existing services
* **Circular Dependency Detection** - Identify and report circular dependencies with detailed context
* **PHPStan Level 8 Support** - Comprehensive type safety with generics and template types
* **Detailed Error Reporting** - All exceptions include dependency chain visualization and context

## Requirements

- PHP 8.1 or higher
- PSR Container 2.0+ package

## Installation

You can install the package via composer:

```bash
composer require wptechnix/di-container
```

## Basic Usage

### Creating a Container

```php
use WPTechnix\DI\Container;

// Create a new container instance
$container = new Container();
```

### Binding Services

The container offers four main ways to register services with different lifecycles:

#### 1. Basic Binding (New Instance Each Time)

Use `bind()` when you want a new instance created each time the service is resolved:

```php
// Basic binding: new instance each time
$container->bind(LoggerInterface::class, FileLogger::class);

// With a factory closure
$container->bind(LoggerInterface::class, function ($container) {
    return new FileLogger('/path/to/logs');
});
```

#### 2. Singleton Binding (Same Instance Every Time)

Use `singleton()` when you want the same instance shared across the application:

```php
// Interface to implementation
$container->singleton(LoggerInterface::class, FileLogger::class);

// For concrete classes, the implementation is optional
$container->singleton(Database::class); // Equivalent to: singleton(Database::class, Database::class)

// With a factory closure
$container->singleton(Config::class, function ($container) {
    return new Config(['env' => 'production']);
});
```

#### 3. Factory Binding (Custom Creation Logic)

Use `factory()` for complex instantiation logic (creates new instance each time):

```php
$container->factory(RequestHandler::class, function ($container, $params) {
    $logger = $container->get(LoggerInterface::class);
    $timeout = $params['timeout'] ?? 30;
    return new RequestHandler($logger, $timeout);
});
```

#### 4. Instance Binding (Existing Object)

Use `instance()` to register an already created object:

```php
$config = new Config(['debug' => true]);
$container->instance(Config::class, $config);
```

#### The Underlying Bind Method

All binding methods are built on top of the core `bind()` method:

```php
// Full bind() method signature:
// bind(id, implementation, shared = false, override = false)

// These two are equivalent:
$container->singleton(LoggerInterface::class, FileLogger::class);
$container->bind(LoggerInterface::class, FileLogger::class, true);

// These two are equivalent:
$container->factory(RequestHandler::class, $factoryClosure);
$container->bind(RequestHandler::class, $factoryClosure, false);
```

#### Type-Safety Benefits

Using interfaces or class names as service IDs provides better type hinting:

```php
// Using interface as ID - great for type hinting
$logger = $container->get(LoggerInterface::class); // IDE knows this returns LoggerInterface

// Using string as ID - works but loses type hinting
$container->bind('logger', FileLogger::class);
$logger = $container->get('logger'); // IDE doesn't know the return type
```


### Resolving Services

The container provides type-safe resolution through PHP's type system:

```php
// Resolve a class with dependencies - fully type-hinted in IDEs
$logger = $container->get(LoggerInterface::class); // IDE knows this returns LoggerInterface

// This works because of PHP's generics support in PHPDoc and is enforced by PHPStan
/** @var FileLogger $specificLogger */
$specificLogger = $container->get(LoggerInterface::class);

// Use resolve method with parameters
$controller = $container->resolve(UserController::class, [
    'userId' => 123
]);

// Type-safety with resolve is also preserved
/** @var UserController $controller */
$controller = $container->resolve(UserController::class);
```

### Using Contextual Bindings

Contextual bindings allow you to specify different implementations based on where the dependency is being used:

```php
// Register different logger implementations based on context
$container->when(UserController::class)
          ->needs(LoggerInterface::class)
          ->give(UserLogger::class);

$container->when(PaymentController::class)
          ->needs(LoggerInterface::class)
          ->give(PaymentLogger::class);
```

### Attribute-Based Injection

Property injection can be performed using PHP 8 attributes:

```php
use WPTechnix\DI\Attributes\Inject;

class UserRepository 
{
    #[Inject]
    public LoggerInterface $logger;
    
    #[Inject(CacheService::class)]
    public CacheInterface $cache;
}
```

### Service Providers

Organize related bindings in provider classes:

```php
use WPTechnix\DI\Contracts\ProviderInterface;
use WPTechnix\DI\Container;

class LoggingServiceProvider implements ProviderInterface 
{
    public function __construct(
        protected Container $container
    ) {}

    public function register(): void
    {
        $this->container->singleton(LoggerInterface::class, FileLogger::class);
        $this->container->singleton(LoggerFactory::class);
    }
}

// Register the provider
$container->provider(LoggingServiceProvider::class);
```

### Service Tagging

Group related services for collective resolution. Using interfaces as tag names provides better type safety and IDE autocomplete support:

```php
// Using string tags (works, but lacks type safety)
$container->tag('validators', [
    EmailRule::class,
    PasswordRule::class,
    UsernameRule::class
]);

// Resolve with string tag
$validators = $container->resolveTagged('validators');

// RECOMMENDED: Using interfaces as tags for better type hinting
$container->tag(ValidationRuleInterface::class, [
    EmailRule::class, // All these classes should implement ValidationRuleInterface
    PasswordRule::class,
    UsernameRule::class
]);

// Resolve with interface - provides proper type hinting in IDE
$validators = $container->resolveTagged(ValidationRuleInterface::class); // $validators will be ValidationRuleInterface[]
```

## WordPress Integration

The container integrates seamlessly with WordPress:

```php
// In your main plugin file
class MyPlugin {
    private Container $container;
    
    public function __construct() {
        $this->container = new Container();
        $this->registerServices();
        $this->boot();
    }
    
    private function registerServices(): void {
        // Register core WordPress services
        $this->container->instance('wpdb', $GLOBALS['wpdb']);
        
        // Register plugin services
        $this->container->provider(SettingsServiceProvider::class);
        $this->container->provider(AdminServiceProvider::class);
        $this->container->provider(FrontendServiceProvider::class);
    }
    
    private function boot(): void {
        // Resolve and initialize hooks manager
        $hooks = $this->container->get(HooksManager::class);
        $hooks->register();
    }
}
```

## Advanced Usage

### Service Extension

Extend or decorate existing services:

```php
$container->extend(LoggerInterface::class, function ($logger, $container) {
    return new LoggerDecorator($logger, $container->get(MetricsService::class));
});
```

### Unbinding Services

Remove a service from the container:

```php
$container->unbind(ServiceInterface::class);
```

### Forgetting Contextual Bindings

Remove contextual bindings:

```php
// Remove all contextual bindings for UserController
$container->forgetWhen(UserController::class);

// Remove specific contextual binding
$container->forgetWhen(UserController::class, LoggerInterface::class);
```

## Error Handling

The container provides a comprehensive exception hierarchy with detailed diagnostic information:

- `ServiceNotFoundException`: When a requested service cannot be found
- `ServiceAlreadyBoundException`: When a service is already bound and override is not set to true
- `CircularDependencyException`: When circular dependencies are detected
- `AutowiringException`: When a dependency cannot be autowired
- `BindingException`: When a binding cannot be registered
- `InstantiationException`: When a class cannot be instantiated
- `ResolutionException`: When a class cannot be resolved
- `InjectionException`: When property or method injection fails

All exceptions provide detailed context including dependency chains for easier debugging.

## Testing

```bash
composer test
```

## PHPStan Analysis

```bash
composer phpstan
```

## PHP CodeSniffer

```bash
composer phpcs
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for version update information.

## Contributing

We welcome contributions to this package. Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please email security@wptechnix.com.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [WPTechnix](https://github.com/wptechnix) - Creator and maintainer
