# Grouping Services with Tags

Tags allow you to group services and resolve them as a collection.  
Use tags when you have many services that share a common purpose but are registered separately.

## Attaching a Tag

After registering a service, call `->tag()` on the definition:

```php
use App\Console\ServeCommand;
use App\Console\MigrateCommand;

$container->singleton(ServeCommand::class)->tag('console.command');
$container->singleton(MigrateCommand::class)->tag('console.command');
```

You can attach multiple tags to a single service:

```php
$container->singleton(ServeCommand::class)
    ->tag('console.command')
    ->tag('cli.runnable');
```

Duplicate tags are silently ignored.

## Resolving Tagged Services

Call `$container->tagged('tag.name')` to resolve all services that carry that tag.  
The result is a list in **registration order**.

```php
$commands = $container->tagged('console.command');

foreach ($commands as $command) {
    // $command is a fully resolved instance
}
```

## Factories and Singletons

The tag works identically for both singletons and factories.  
If a service is a factory, every call to `tagged()` will produce a **fresh** instance for that service.

```php
$container->factory(SomeReport::class)->tag('report');

$firstSet  = $container->tagged('report');
$secondSet = $container->tagged('report');
// $firstSet[0] !== $secondSet[0]
```

If you need the same set of instances, make those services singletons.

## Practical Use Cases

### CLI Commands

```php
class ConsoleKernel
{
    public function __construct(private Container $container) {}

    public function handle(string $commandName): void
    {
        foreach ($this->container->tagged('console.command') as $command) {
            if ($command->getName() === $commandName) {
                $command->execute();
                return;
            }
        }
    }
}
```

### Event Subscribers

```php
$container->singleton(UserCreatedSubscriber::class)->tag('event.subscriber');
$container->singleton(OrderPlacedSubscriber::class)->tag('event.subscriber');

// In your event dispatcher boot
foreach ($container->tagged('event.subscriber') as $subscriber) {
    $dispatcher->addSubscriber($subscriber);
}
```

### Middleware Pipeline

```php
$container->singleton(AuthMiddleware::class)->tag('http.middleware');
$container->singleton(CorsMiddleware::class)->tag('http.middleware');

// Build the pipeline
$middlewares = $container->tagged('http.middleware');
```

## Checking for Tags

You can inspect the `Definition` object to see if it has a tag:

```php
$def = $container->singleton(SomeClass::class)->tag('group.a');
$def->hasTag('group.a'); // true
$def->hasTag('group.b'); // false
$def->getTags();         // ['group.a']
```

## Empty Tags

Calling `tagged()` with an unknown tag returns an empty array — no exception is thrown.

```php
$services = $container->tagged('nonexistent'); // []
```

## Summary

- Tag services with `->tag()`.
- Resolve all tagged services with `$container->tagged()`.
- Resolution order matches registration order.
- Works with both singletons and factories.
- Unknown tags return an empty array.

---

**Previous:** [Passing Constructor Parameters](Constructor-Parameters.md) · **Next:** [Decorating Services with Extenders](Extending-Services.md)
