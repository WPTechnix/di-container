# Changelog

All notable changes to the WPTechnix DI Container will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2025-03-29

Initial release of the WPTechnix DI Container.

### Added

- **PSR-11 Compliance** - Full implementation of the PSR-11 container interface standard
- **Autowiring** - Automatic dependency resolution based on type-hints
- **Constructor Injection** - Automatic resolution of constructor parameters
- **Property Injection** - Support for injecting dependencies via PHP 8 attributes on properties
- **Method/Setter Injection** - Support for injecting dependencies via setter methods
- **Contextual Bindings** - Ability to define context-specific dependencies:
    - `when()->needs()->give()` fluent interface
    - Support for multiple concrete classes in a single binding
- **Service Registration**:
    - `bind()` - Register a service with the container
    - `singleton()` - Register a shared instance of a service
    - `factory()` - Register a factory for creating service instances
    - `instance()` - Register an existing instance with the container
- **Service Providers** - Organize related bindings in provider classes:
    - Support for `register()` and `boot()` methods
    - Lazy-loading of provider instances
- **Service Tagging** - Tag and group related services:
    - `tag()` - Add tags to services
    - `resolve_tagged()` - Resolve all services with a specific tag
    - `untag()` - Remove tags from services
- **Interface Binding** - Bind interfaces to concrete implementations
- **Service Extension** - Extend or decorate registered services:
    - `extend()` - Add behavior to existing services
- **Circular Dependency Detection** - Robust detection and reporting of circular dependencies
- **Comprehensive Exception Hierarchy**:
    - `ContainerException` - Base exception for all container errors
    - `ServiceNotFoundException` - When a service can't be found
    - `ServiceAlreadyBoundException` - When a service is already bound
    - `CircularDependencyException` - When circular dependencies are detected
    - `AutowiringException` - When autowiring fails
    - `BindingException` - When a binding can't be registered
    - `InstantiationException` - When a class can't be instantiated
    - `ResolutionException` - When resolution fails for other reasons
    - `InjectionException` - When property or method injection fails
- **PHPStan Level 8 Support** - Comprehensive type safety with generics and template types
- **Detailed Error Reporting** - All exceptions include detailed context information:
    - Dependency chain visualization
    - Detailed error context
    - Original exception preservation
