# API Reference

## Container

```php
namespace WPTechnix\DI;

final class Container implements Psr\Container\ContainerInterface
```

### singleton()

```php
public function singleton(
    string $id,
    string|callable|null $resolver = null,
    bool $override = false,
): Definition
```

Register a shared service. The factory runs once; the instance is cached for subsequent resolutions.

| Parameter | Type | Description |
|---|---|---|
| `$id` | `string` | The service identifier (prefer `ClassName::class` or `InterfaceName::class`) |
| `$resolver` | `string\|callable\|null` | Closure, class-string, or null (ID used as class) |
| `$override` | `bool` | Replace an existing registration, including alias identifiers |

| Throws | Condition |
|---|---|
| `DuplicateServiceException` | ID already exists and `$override` is false |
| `ContainerException` | String/null resolver names a non-existent class |

### factory()

```php
public function factory(
    string $id,
    string|callable|null $resolver = null,
    bool $override = false,
): Definition
```

Register a non-shared service. The resolver runs on every `get()`; no caching.

| Parameter | Type | Description |
|---|---|---|
| `$id` | `string` | The service identifier |
| `$resolver` | `string\|callable\|null` | Closure, class-string, or null (ID used as class) |
| `$override` | `bool` | Replace an existing registration, including alias identifiers |

| Throws | Condition |
|---|---|
| `DuplicateServiceException` | ID already exists and `$override` is false |
| `ContainerException` | String/null resolver names a non-existent class |

### get()

```php
public function get(string $id): mixed
```

Resolve a service by its identifier or alias.

Use `has()` to check for optional services before calling `get()`. `has()` returns `false` for unregistered identifiers and for dangling aliases, and does not throw for missing services.

| Throws | Condition |
|---|---|
| `ServiceNotFoundException` | No definition or resolvable alias for the identifier |
| `ContainerException` | Circular dependency or circular alias detected |

### has()

```php
public function has(string $id): bool
```

Check whether a service or alias is registered. Returns false for dangling aliases (alias exists but target has no definition).

| Throws | Condition |
|---|---|
| `ContainerException` | Circular alias chain |

### alias()

```php
public function alias(string $alias, string $id): void
```

Point an identifier at another identifier. No factory — resolving the alias resolves the target.

To replace an existing alias with a new definition, use `singleton()` or `factory()` with `override: true` on the alias identifier.

| Throws | Condition |
|---|---|
| `ContainerException` | Alias references itself |
| `DuplicateServiceException` | Alias identifier is already registered |

### extend()

```php
public function extend(string $id, callable $extender): void
```

Decorate a registered service before resolution. Extenders run in registration order.

Call `extend()` before the first `get()` for the target service. The `register()` phase of a service provider is the recommended place.

The extender callable signature: `callable(mixed $instance, Container $c): mixed`

| Throws | Condition |
|---|---|
| `ServiceNotFoundException` | No service registered for the identifier |
| `ServiceAlreadyResolvedException` | Singleton already resolved |
| `ContainerException` | Circular alias chain |

### tagged()

```php
public function tagged(string $tag): list<mixed>
```

Resolve all services carrying the given tag, in registration order.

| Throws | Condition |
|---|---|
| *(none)* | Unknown tags return `[]` |

### provider()

```php
public function provider(ServiceProvider $provider): self
```

Queue a service provider. Returns `$this` for fluent chaining.

| Throws | Condition |
|---|---|
| `FrozenContainerException` | Container has already been booted |

### boot()

```php
public function boot(): void
```

Register then boot all queued providers (two-phase execution). Sets the booted flag before any provider's `register()` runs, freezing further provider registration.

| Throws | Condition |
|---|---|
| `FrozenContainerException` | Container has already been booted |

---

## Definition

```php
namespace WPTechnix\DI;

final class Definition
```

Returned by `singleton()` and `factory()` for fluent configuration.

### tag()

```php
public function tag(string ...$tags): self
```

Attach one or more tags. Duplicate tags are silently ignored. Returns `$this`.

### hasTag()

```php
public function hasTag(string $tag): bool
```

Check whether the service carries a specific tag.

### getTags()

```php
public function getTags(): list<string>
```

Return all attached tags.

### addParameter()

```php
public function addParameter(mixed $value, bool $resolve = false): self
```

Append a single positional parameter. When `$resolve` is true, strings are fetched as services and Closures are invoked with the container. Returns `$this`.

### addParameters()

```php
public function addParameters(array $values): self
```

Replace all parameters with a plain list. Every value is stored with `resolve: false`. Returns `$this`.

> **Important:** `addParameters()` does not support the `resolve` flag. If any parameter is a service dependency, use `addParameter(ClassName::class, resolve: true)` instead.

### getParameters()

```php
public function getParameters(): list<array{value: mixed, resolve: bool}>
```

Return all stored parameters in order, each with its resolve flag.

### getId()

```php
public function getId(): string
```

Return the service identifier.

### isShared()

```php
public function isShared(): bool
```

Whether the service is a singleton (cached after first resolution).

### isResolved()

```php
public function isResolved(): bool
```

Whether the service has been resolved at least once.

---

## ServiceProvider Interface

```php
namespace WPTechnix\DI;

interface ServiceProvider
{
    public function register(Container $container): void;
    public function boot(Container $container): void;
}
```

| Method | Contract |
|---|---|
| `register()` | Bind services. Must not resolve services. All `register()` calls complete before any `boot()` call. |
| `boot()` | Wire things together. Safe to resolve any service. Runs after all `register()` calls. |

---

## Exception Table

| Method | Exception | Condition |
|---|---|---|
| `singleton()` | `DuplicateServiceException` | ID already exists and `override` is false |
| `singleton()` | `ContainerException` | String/null resolver names a non-existent class |
| `factory()` | `DuplicateServiceException` | ID already exists and `override` is false |
| `factory()` | `ContainerException` | String/null resolver names a non-existent class |
| `get()` | `ServiceNotFoundException` | No definition or resolvable alias |
| `get()` | `ContainerException` | Circular dependency or circular alias |
| `has()` | `ContainerException` | Circular alias chain |
| `alias()` | `ContainerException` | Self-referencing alias |
| `alias()` | `DuplicateServiceException` | Alias identifier already registered |
| `extend()` | `ServiceNotFoundException` | No service for the identifier |
| `extend()` | `ServiceAlreadyResolvedException` | Singleton already resolved |
| `extend()` | `ContainerException` | Circular alias chain |
| `tagged()` | *(none)* | Unknown tags return `[]` |
| `provider()` | `FrozenContainerException` | Container already booted |
| `boot()` | `FrozenContainerException` | Container already booted |

---

## Callable Signatures

**Resolver:**
```
callable(Container $c, array<int, mixed> $params): mixed
```

**Extender:**
```
callable(mixed $instance, Container $c): mixed
```

---

**Previous:** [Service Providers](Service-Providers.md)
