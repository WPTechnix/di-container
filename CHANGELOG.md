# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-01

### Added

- PSR-11 compliant container implementing `Psr\Container\ContainerInterface` with
  `singleton()`, `factory()`, `get()`, and `has()` methods

- **Definition fluent API** — every registration returns a `Definition` object
  allowing tags, parameters, and extenders to be chained before resolution

- **Service providers** — two-phase bootstrapping via the `ServiceProvider`
  interface (`register()` / `boot()`), where all providers are registered before
  any are booted so each provider can rely on services from other providers

- **Aliasing** — `Container::alias()` to point one identifier to another, with
  transitive alias resolution and circular alias detection

- **Constructor parameters** — `Definition::addParameter()` with an optional
  `resolve` flag: strings resolve as services, closures receive the container,
  and raw values pass through unchanged; `addParameters()` replaces all
  parameters at once

- **Tagging** — `Definition::tag()` to attach one or more tags to a service,
  `Container::tagged()` to resolve every service carrying a given tag in
  registration order

- **Service extenders** — `Container::extend()` to decorate a service before
  first resolution, with stacking support (extenders run in registration order)
  and immutability for already-resolved singletons

- **String and null class-name resolvers** — register a service by passing a
  class-string (auto-instantiated with constructor parameters) or `null` (class
  name inferred from the service identifier); non-existent classes fail fast at
  registration time

- **Duplicate service detection** — re-registering an identifier throws
  `DuplicateServiceException`; pass `override: true` to replace existing
  registrations

- **Circular dependency detection** — `ContainerException::circularDependency()`
  thrown when a resolution chain loops back on itself, with the full chain in
  the message

- **Frozen container protection** — once `boot()` is called, no new providers
  or service definitions can be added; `FrozenContainerException` enforces
  immutability

- **Exception hierarchy** — five exception classes all extending
  `ContainerException` (which implements `ContainerExceptionInterface`):
  `DuplicateServiceException`, `ServiceNotFoundException` (also implements
  `NotFoundExceptionInterface`), `ServiceAlreadyResolvedException`,
  `FrozenContainerException`

### Testing

- 90+ PHPUnit tests across 9 test suites covering the container, aliases,
  parameters, service providers, tagging, extenders, string factories,
  exception hierarchy, and circular dependency detection

### Documentation

- Full documentation wiki with guides on core concepts, constructor parameters,
  tagging, extending services, service providers, and API reference

[1.0.0]: https://github.com/wptechnix/di-container/releases/tag/v1.0.0
