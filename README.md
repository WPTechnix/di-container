# PSR-11 Dependency Injection Container for PHP 8.1+

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wptechnix/di-container.svg)](https://packagist.org/packages/wptechnix/di-container)
[![Tests](https://github.com/wptechnix/di-container/actions/workflows/tests.yml/badge.svg)](https://github.com/wptechnix/di-container/actions/workflows/tests.yml)
[![PHPStan](https://github.com/wptechnix/di-container/actions/workflows/phpstan.yml/badge.svg)](https://github.com/wptechnix/di-container/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/wptechnix/di-container.svg)](https://packagist.org/packages/wptechnix/di-container)
[![Monthly Downloads](https://img.shields.io/packagist/dm/wptechnix/di-container.svg)](https://packagist.org/packages/wptechnix/di-container)
[![License](https://img.shields.io/github/license/wptechnix/di-container)](https://github.com/wptechnix/di-container/blob/main/LICENSE)

A lightweight, PSR-11 compliant dependency injection container designed specifically for WordPress plugin development, featuring autowiring, contextual bindings, attribute-based injection, and service providers.

## Features

* **PSR-11 Compliant** - Follows the [PHP-FIG PSR-11](https://www.php-fig.org/psr/psr-11/) container standard
* **Autowiring** - Automatic resolution of dependencies
* **Constructor Injection** - Automatic dependency resolution through constructor parameters
* **Property Injection** - Inject dependencies using PHP 8 attributes on class properties
* **Method/Setter Injection** - Automatic dependency resolution through setter methods
* **Contextual Bindings** - Define specific implementations for different contexts
* **Service Providers** - Organize related bindings and bootstrapping logic
* **Service Tagging** - Group related services for collective resolution
* **Interface Binding** - Easily bind interfaces to implementations
* **Singleton & Factory Patterns** - Register services as shared instances or factories
* **Service Extension** - Decorate and extend existing services
* **Circular Dependency Detection** - Identify and report circular dependencies with detailed context
* **Comprehensive Exception Hierarchy** - Detailed error reporting with full context for debugging
* **Extensible Architecture** - Override bindings and extend services when needed

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

```php
// Bind an interface to a concrete implementation
$container->bind(LoggerInterface::class, FileLogger::class);

// Register a singleton
$container->singleton(Database::class);

// Register a factory (creates a new instance each time)
$container->factory(RequestHandler::class, function ($container) {
    return new RequestHandler($container->get(LoggerInterface::class));
});

// Register an existing instance
$config = new Config(['debug' => true]);
$container->instance(Config::class, $config);
```

### Resolving Services

```php
// Resolve a class with dependencies
$logger = $container->get(LoggerInterface::class);

// Or use the resolve method with parameters
$controller = $container->resolve(UserController::class, [
    'userId' => 123
]);
```

### Using Contextual Bindings

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

```php
use WPTechnix\DI\Contracts\ProviderInterface;
use WPTechnix\DI\Contracts\ContainerInterface;

class LoggingServiceProvider implements ProviderInterface 
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(LoggerInterface::class, FileLogger::class);
        $container->singleton(LoggerFactory::class);
    }
    
    public function boot(ContainerInterface $container): void
    {
        // Bootstrap the service if needed
        $logger = $container->get(LoggerInterface::class);
        $logger->info('Logging service started');
    }
}

// Register the provider
$container->provider(LoggingServiceProvider::class);
```

### Service Tagging

```php
// Tag services for collective resolution
$container->tag('validators', [
    EmailRule::class,
    PasswordRule::class,
    UsernameRule::class
]);

// Resolve all validators
$validators = $container->resolveTagged('validators');
```

## WordPress Integration

This container was designed with WordPress plugin development in mind:

```php
// In your main plugin file
class MyPlugin {
    private ContainerInterface $container;
    
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

## Error Handling

The container provides detailed exceptions for diagnostic purposes:

- `ServiceNotFoundException`: When a requested service cannot be found
- `ServiceAlreadyBoundException`: When a service is already bound and override is not set to true
- `CircularDependencyException`: When circular dependencies are detected
- `AutowiringException`: When a dependency cannot be autowired
- `BindingException`: When a binding cannot be registered
- `InstantiationException`: When a class cannot be instantiated such as abstract class or non-public constructors
- `ResolutionException`: When a class cannot be resolved or any ReflectionException
- `InjectionException`: When property or method injection fails


## Documentation

Visit our [documentation](https://wptechnix.github.io/di-container) for full API details.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Contributing

We welcome contributions to this package. Please see [CONTRIBUTING](CONTRIBUTING.md) for details on:

- Reporting bugs
- Suggesting features
- Submitting pull requests
- Our coding standards (PSR-12 and PHPStan level 8)
- Development workflow

## Security

If you discover a security vulnerability, please email security@wptechnix.com.

## Support

For questions and help, create an issue on GitHub or contact developer@wptechnix.com.

## Credits

- [WPTechnix](https://github.com/wptechnix) - Creator and maintainer

## Acknowledgements

This package leverages several outstanding tools from the PHP ecosystem:

- [PHPUnit](https://phpunit.de/) - Testing framework
- [PHPStan](https://phpstan.org/) - Static analysis tool
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) - Coding standards
- [phpDocumentor](https://www.phpdoc.org/) - Documentation generation

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
