<?php

/**
 * Container implementation class.
 *
 * This file defines the Container class, a PSR-11 compatible dependency injection container
 * with additional functionality for WordPress plugin development.
 *
 * @package WPTechnix\DI
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI;

use Closure;
use Psr\Container\ContainerInterface;
use WPTechnix\DI\Attributes\Inject;
use WPTechnix\DI\Contracts\ProviderInterface;
use WPTechnix\DI\Exceptions\AutowiringException;
use WPTechnix\DI\Exceptions\BindingException;
use WPTechnix\DI\Exceptions\CircularDependencyException;
use WPTechnix\DI\Exceptions\ContainerException;
use WPTechnix\DI\Exceptions\InjectionException;
use WPTechnix\DI\Exceptions\InstantiationException;
use WPTechnix\DI\Exceptions\ResolutionException;
use WPTechnix\DI\Exceptions\ServiceAlreadyBoundException;
use WPTechnix\DI\Exceptions\ServiceNotFoundException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Throwable;

/**
 * Container implementation for dependency injection.
 *
 * The Container class provides a robust dependency injection implementation
 * specifically designed for WordPress plugin development. It follows PSR-11
 * container standards while providing WordPress-specific features and
 * optimization.
 *
 * Features:
 * - Automatic dependency resolution
 * - Singleton and factory patterns support
 * - Interface-to-implementation binding
 * - Contextual bindings
 * - Service providers
 * - Service tagging
 * - Extension and decoration of services
 *
 * @package WPTechnix\DI
 * @author WPTechnix <developers@wptechnix.com>
 */
class Container implements ContainerInterface
{
    /**
     * Service definitions.
     *
     * @var array<string, array{
     *   concrete: Closure(self, array<string, mixed>): object|string,
     *   shared: bool
     * }>
     */
    private array $bindings = [];

    /**
     * Contextual bindings.
     *
     * @var array<string, array<string, array{
     *   concrete: Closure(self, array<string, mixed>): object|string,
     *   shared: bool
     * }>>
     */
    private array $contextualBindings = [];

    /**
     * Resolved singleton instances.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Currently resolving services stack.
     *
     * @var array<string, true>
     */
    private array $resolving = [];

    /**
     * Service tags.
     *
     * @var array<string, array<string>>
     */
    private array $tags = [];

    /**
     * Service extensions.
     *
     * @var array<string, array<(Closure(object, self): object)>>
     */
    private array $extensions = [];

    /**
     * Current dependency resolution chain.
     *
     * @var array<string>
     */
    private array $dependencyChain = [];

    /**
     * Constructor.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function __construct()
    {

        $this->instance(Container::class, $this);
    }

    /**
     * Register an existing instance as a singleton.
     *
     * This method allows registering an already instantiated object within the container
     * as a singleton, making it available for future retrievals.
     *
     * @template T of object
     *
     * @param string|class-string<T> $id Service identifier or class name.
     * @param object $instance Instance to register.
     *
     * @phpstan-param ( $id is class-string<T> ? T : object ) $instance Instance to register.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @example
     *  ```php
     *  $logger = new Logger();
     *  $container->instance(LoggerInterface::class, $logger);
     *
     *  // Later, the same instance can be retrieved
     *  $sameLogger = $container->get(LoggerInterface::class);
     *  ```
     *
     * @throws ServiceAlreadyBoundException When instance is already registered.
     */
    public function instance(string $id, object $instance): static
    {
        $this->ensureNotBound($id);
        $this->instances[ $id ] = $instance;

        return $this;
    }

    /**
     * Ensures a service is not already bound.
     *
     * Checks if a service identifier already has a binding or instance in the container
     * and throws an exception if it does (unless override is set to true).
     *
     * @param string $id Service identifier.
     * @param bool   $override Whether to override existing bindings.
     *
     * @throws ServiceAlreadyBoundException When service is already bound and override is false.
     */
    private function ensureNotBound(string $id, bool $override = false): void
    {
        if ($this->hasBinding($id) && ! $override) {
            throw new ServiceAlreadyBoundException(
                $id,
                $this->dependencyChain,
                [
                 'existing_binding' => [
                    'type'   => isset($this->bindings[ $id ]) ? 'binding' : 'instance',
                    'shared' => isset($this->bindings[ $id ]) ? $this->bindings[ $id ]['shared'] : true,
                 ],
                ]
            );
        }
    }

    /**
     * Check if a service has been explicitly bound.
     *
     * Unlike the standard 'has' method which checks if a service can be resolved,
     * this method strictly checks if a binding has been explicitly registered.
     *
     * @param string $id Service identifier.
     *
     * @return bool Returns true if the identifier has been bound, false otherwise.
     *
     * @example
     *  ```php
     *  $container->bind(UserRepositoryInterface::class, UserRepository::class);
     *
     *  // Check if a specific service is explicitly bound
     *  $isBound = $container->hasBinding(UserRepositoryInterface::class);
     *  ```
     */
    public function hasBinding(string $id): bool
    {
        return isset($this->bindings[ $id ]) || isset($this->instances[ $id ]);
    }

    /**
     * Resets the container to its initial state.
     *
     * Removes all bindings, instances, aliases, and other registered services
     * from the container, effectively resetting it to a clean state.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @example
     *  ```php
     *  // After using the container extensively
     *  $container->reset(); // Clears all bindings and registered services
     *  ```
     */
    public function reset(): static
    {
        $this->bindings           = [];
        $this->instances          = [];
        $this->resolving          = [];
        $this->tags               = [];
        $this->extensions         = [];
        $this->dependencyChain   = [];
        $this->contextualBindings = []; // Reset contextual bindings as well

        $this->instances[ Container::class ] = $this;

        return $this;
    }

    /**
     * Binds a factory callback.
     *
     * Factory bindings provide a way to define how an object should be created
     * each time it is requested from the container.
     *
     * @template T of object
     * @param string|class-string<T> $id Service identifier.
     * @param ( $id is class-string<T> ?
     *          (Closure(self, array<string, mixed>): T ) :
     *          (Closure(self, array<string, mixed>): object)
     *        ) $factory Factory callback.
     * @param bool $override True to override preregistered implementation if there's any.
     *                       Default false.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ServiceAlreadyBoundException When service is already bound.
     * @throws BindingException When factory cannot be registered.
     *
     * @example
     *  ```php
     *  $container->factory(DatabaseConnection::class, function($container, $params) {
     *      return new DatabaseConnection(
     *          $params['host'] ?? 'localhost',
     *          $params['port'] ?? 3306
     *      );
     *  });
     *
     *  // When retrieved, a new instance is created each time
     *  $db1 = $container->get(DatabaseConnection::class, ['host' => 'server1']);
     *  $db2 = $container->get(DatabaseConnection::class, ['host' => 'server2']);
     *  ```
     */
    public function factory(string $id, Closure $factory, bool $override = false): static
    {
        return $this->bind($id, $factory, false, $override);
    }

    /**
     * Binds an interface or class to a concrete implementation.
     *
     * This method allows associating an abstract type (interface or class) with
     * a concrete implementation that should be instantiated when the abstract type
     * is requested.
     *
     * @template T of object
     * @param string|class-string<T> $id Abstract type identifier.
     * @param ( $id is class-string<T> ?
     *          ( class-string<T>|(Closure(self, array<string, mixed>): T) ) :
     *         ( string|(Closure(self, array<string, mixed>): object) )
     *       ) $implementation Concrete class name or factory closure.
     * @param bool $shared Whether to share the instance across multiple resolutions.
     * @param bool $override True to override preregistered implementation if there's any.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ServiceAlreadyBoundException When service is already bound.
     * @throws BindingException When binding cannot be registered.
     *
     * @example
     *  ```php
     *  // Bind an interface to a concrete implementation
     *  $container->bind(UserRepositoryInterface::class, UserRepository::class);
     *
     *  // Bind with custom instantiation
     *  $container->bind(ConfigInterface::class, function($container) {
     *      return new Config('/path/to/config.json');
     *  });
     *  ```
     */
    public function bind(
        string $id,
        string|Closure $implementation,
        bool $shared = false,
        bool $override = false
    ): static {

        $this->ensureNotBound($id, $override);

        if (is_string($implementation) && ! class_exists($implementation)) {
            throw new BindingException(
                sprintf('Implementation class "%s" does not exist', $implementation),
                $id,
                $this->dependencyChain,
                [
                 'implementation' => $implementation,
                ]
            );
        }

        $concrete = is_string($implementation)
          ? fn(Container $container, $parameters) => $container->resolveClass($implementation, $parameters)
          : $implementation;

        $this->bindings[ $id ] = [
          'concrete' => $concrete,
          'shared'   => $shared,
        ];

        return $this;
    }

    /**
     * Begin a new contextual binding.
     *
     * Contextual bindings allow for different implementations of a dependency
     * based on the context in which it is being resolved.
     *
     * @param string|array<string> $concrete The concrete class(es) that should receive a contextual binding.
     *
     * @return ContextualBindingBuilder A builder interface to specify the contextual binding.
     *
     * @example
     *  ```php
     *  // Different logger for AdminService vs. UserService
     *  $container->when(AdminService::class)
     *           ->needs(LoggerInterface::class)
     *           ->give(AdminLogger::class);
     *
     *  $container->when(UserService::class)
     *           ->needs(LoggerInterface::class)
     *           ->give(UserLogger::class);
     *  ```
     */
    public function when(string|array $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, is_array($concrete) ? $concrete : [ $concrete ]);
    }

    /**
     * Add a contextual binding to the container.
     *
     * Direct method to register a contextual binding without using the builder pattern.
     *
     * @param string         $concrete       The concrete class that receives the contextual binding.
     * @param string         $abstract       The abstract type that should be resolved differently.
     * @param string|Closure(self, array<string, mixed>): object $implementation The implementation or factory to use.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws BindingException When the implementation class does not exist.
     *
     * @example
     *  ```php
     *  // Directly add a contextual binding
     *  $container->addContextualBinding(
     *      AdminService::class,
     *      LoggerInterface::class,
     *      AdminLogger::class
     *  );
     *  ```
     */
    public function addContextualBinding(string $concrete, string $abstract, string|Closure $implementation): static
    {
        if (! isset($this->contextualBindings[ $concrete ])) {
            $this->contextualBindings[ $concrete ] = [];
        }

        if (is_string($implementation) && ! class_exists($implementation)) {
            throw new BindingException(
                sprintf('Implementation class "%s" does not exist', $implementation),
                $abstract,
                $this->dependencyChain,
                [
                 'implementation' => $implementation,
                 'concrete'       => $concrete,
                ]
            );
        }

        $concreteBinding = is_string($implementation)
          ? fn(Container $container, $parameters) => $container->resolveClass($implementation, $parameters)
          : $implementation;

        $this->contextualBindings[ $concrete ][ $abstract ] = [
          'concrete' => $concreteBinding,
          'shared'   => false, // Contextual bindings are typically not shared
        ];

        return $this;
    }

    /**
     * Resolve a method's dependencies.
     *
     * Analyzes a method's parameters and resolves each dependency from the container
     * or provided parameters.
     *
     * @param ReflectionMethod $method The method to resolve dependencies for.
     * @param array<string, mixed> $parameters Method parameters.
     *
     * @return array<int|string, mixed>
     *
     * @throws AutowiringException When a parameter cannot be autowired.
     * @throws ServiceNotFoundException When a dependency cannot be found.
     * @throws CircularDependencyException When circular dependencies are detected.
     * @throws ResolutionException When other resolution errors occur.
     */
    private function resolveMethodDependencies(ReflectionMethod $method, array $parameters = []): array
    {
        $className  = $method->getDeclaringClass()->getName();
        $methodName = $method->getName();

        $dependencies = [];

        foreach ($method->getParameters() as $param) {
            $paramName     = $param->getName();
            $paramPosition = $param->getPosition();

            // Use provided parameter if available (by name)
            if (array_key_exists($paramName, $parameters)) {
                $dependencies[ $paramPosition ] = $parameters[ $paramName ];
                continue;
            }

            $type = $param->getType();

            // Cannot autowire parameters with no type hint.
            if (null === $type) {
                // Only throw if there's no default value.
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[ $paramPosition ] = $param->getDefaultValue();
                    continue;
                }

                throw new AutowiringException(
                    $className,
                    $paramName,
                    'unknown',
                    $this->dependencyChain,
                    [
                     'method'      => $methodName,
                     'is_optional' => $param->isOptional(),
                     'is_variadic' => $param->isVariadic()
                    ]
                );
            }

            // Cannot autowire union types.
            if ($type instanceof ReflectionUnionType) {
                // Only throw if there's no default value.
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[ $paramPosition ] = $param->getDefaultValue();
                    continue;
                }

                // Allow null for nullable parameters.
                if ($type->allowsNull()) {
                    $dependencies[ $paramPosition ] = null;
                    continue;
                }

                $typeNames = array_map(
                    function ($t) {
                        $callback = [ $t, 'getName' ];

                        return is_callable($callback) ? call_user_func($callback) : '';
                    },
                    $type->getTypes()
                );

                throw new AutowiringException(
                    $className,
                    $paramName,
                    'union type',
                    $this->dependencyChain,
                    [
                     'method'      => $methodName,
                     'union_types' => $typeNames
                    ]
                );
            }

            // Now we know it's a ReflectionNamedType.
            /** @var ReflectionNamedType $type */

            // Cannot autowire built-in types (int, string, etc.) without default values.
            if ($type->isBuiltin()) {
                // Only throw if there's no default value.
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[ $paramPosition ] = $param->getDefaultValue();
                    continue;
                }

                // Allow null for nullable types.
                if ($type->allowsNull()) {
                    $dependencies[ $paramPosition ] = null;
                    continue;
                }

                throw new AutowiringException(
                    $className,
                    $paramName,
                    $type->getName(),
                    $this->dependencyChain,
                    [
                     'method'     => $methodName,
                     'is_builtin' => true
                    ]
                );
            }

            // At this point, we have a class or interface type.
            $dependencyClass = $type->getName();

            /** @var class-string $dependencyClass */

            try {
                // First, check for contextual binding
                if (isset($this->contextualBindings[ $className ][ $dependencyClass ])) {
                    $binding        = $this->contextualBindings[ $className ][ $dependencyClass ];
                    $concrete       = $binding['concrete'];
                    $dependencies[ $paramPosition ] = is_string($concrete) ?
                        $this->resolveClass($concrete) :
                        $concrete($this, []);
                    continue;
                }

                // If no contextual binding exists, try to resolve the dependency.
                $dependencies[ $paramPosition ] = $this->resolve($dependencyClass);
            } catch (ServiceNotFoundException $e) {
                // If the dependency wasn't found but a default value is available, use that.
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[ $paramPosition ] = $param->getDefaultValue();
                    continue;
                }

                // If the type allows null and the dependency wasn't found, use null.
                if ($type->allowsNull()) {
                    $dependencies[ $paramPosition ] = null;
                    continue;
                }

                throw new AutowiringException(
                    $className,
                    $paramName,
                    $dependencyClass,
                    $this->dependencyChain,
                    [
                     'method'             => $methodName,
                     'original_exception' => get_class($e),
                     'original_message'   => $e->getMessage()
                    ]
                );
            } catch (CircularDependencyException $e) {
                // Always propagate circular dependency exceptions.
                throw $e;
            } catch (ContainerException $e) {
                // For other container exceptions, throw an autowiring exception
                // that provides context about which parameter failed.
                throw new AutowiringException(
                    $className,
                    $paramName,
                    $dependencyClass,
                    $this->dependencyChain,
                    [
                     'method'             => $methodName,
                     'original_exception' => get_class($e),
                     'original_message'   => $e->getMessage()
                    ],
                    0,
                    $e
                );
            }
        }

        return $dependencies;
    }

    /**
     * Resolves a binding from the container.
     *
     * Resolves a service based on its binding definition, handling both
     * closure-based factory bindings and class string bindings.
     *
     * @template T of object
     * @param string|class-string<T> $id Service identifier.
     * @param array<string, mixed> $parameters Additional constructor parameters.
     *
     * @return object The resolved service instance.
     *
     * @phpstan-return ( $id is class-string<T> ? T : object )
     *
     * @throws ServiceNotFoundException When the service is not found.
     * @throws CircularDependencyException When circular dependencies are detected.
     * @throws AutowiringException When autowiring fails.
     * @throws InstantiationException When the class cannot be instantiated.
     * @throws ResolutionException When resolution fails for other reasons.
     * @throws ContainerException When unexpected errors occur.
     */
    private function resolveBinding(string $id, array $parameters = []): object
    {
        try {
            $concrete = $this->bindings[ $id ]['concrete'];

            if ($concrete instanceof Closure) {
                return $concrete($this, $parameters);
            }

            // bind() method converts classes to resolvable closures, so this condition would never be reached.
            // However, this is a safeguard to prevent unexpected errors.
            return $this->resolveClass($concrete, $parameters); // @codeCoverageIgnore
        } catch (ContainerException $e) {
            // Let specific exceptions propagate
            throw $e;
        } catch (Throwable $e) {
            throw new ResolutionException(
                sprintf('Failed to resolve binding "%s": %s', $id, $e->getMessage()),
                $id,
                $this->dependencyChain,
                [
                 'parameters' => $parameters,
                 'error_type' => get_class($e),
                ],
                0,
                $e
            );
        }
    }


    /**
     * Resolve a class instance from the container.
     *
     * Creates an instance of the specified class with all dependencies automatically
     * injected through constructor parameters. Validates that the class exists and is instantiable.
     *
     * @template T of object
     * @param string|class-string<T> $className The class to instantiate.
     * @param array<string, mixed> $parameters Constructor parameters to override.
     *
     * @return object The instantiated class.
     *
     * @phpstan-return ( $className is class-string<T> ? T : object )
     *
     * @throws ContainerException If the class cannot be instantiated.
     * @throws ServiceNotFoundException If a dependency cannot be resolved.
     * @throws InstantiationException If the class is not instantiable (abstract, interface, etc.).
     * @throws AutowiringException When constructor parameters cannot be autowired.
     * @throws ResolutionException When reflection errors occur.
     */
    private function resolveClass(string $className, array $parameters = []): object
    {
        try {
            if (! class_exists($className)) {
                throw new ServiceNotFoundException(
                    $className,
                    $this->dependencyChain,
                    [
                     'operation'  => 'resolve_class',
                     'parameters' => $parameters,
                    ]
                );
            }

            $reflection = new ReflectionClass($className);

            if (! $reflection->isInstantiable()) {
                if ($reflection->isAbstract()) {
                    $reason = 'it is an abstract class';
                } else {
                    $reason      = 'it is not instantiable';
                    $constructor = $reflection->getConstructor();
                    if (! empty($constructor) && ! $constructor->isPublic()) {
                        $reason = 'constructor is not public';
                    }
                }

                throw new InstantiationException(
                    $className,
                    $reason,
                    $this->dependencyChain,
                    [
                     'is_interface' => $reflection->isInterface(),
                     'is_abstract'  => $reflection->isAbstract(),
                     'is_trait'     => $reflection->isTrait(),
                     'parameters'   => $parameters,
                    ]
                );
            }

            $constructor = $reflection->getConstructor();

            // If no constructor, just instantiate.
            if (empty($constructor)) {
                $instance = new $className();
            } else {
                // Resolve constructor dependencies.
                $dependencies = $this->resolveMethodDependencies($constructor, $parameters);
                $instance     = $reflection->newInstanceArgs($dependencies);
            }

            return $instance;
        } catch (ContainerException $e) {
            throw $e;
        } catch (ReflectionException $e) {
            // @codeCoverageIgnoreStart
            throw new ResolutionException(
                sprintf('Reflection error when resolving class "%s": %s', $className, $e->getMessage()),
                $className,
                $this->dependencyChain,
                [
                 'parameters' => $parameters,
                ],
                0,
                $e
            );
            // @codeCoverageIgnoreEnd
        } catch (Throwable $e) {
            throw new ContainerException(
                sprintf('Unexpected error resolving class "%s": %s', $className, $e->getMessage()),
                $className,
                $this->dependencyChain,
                [
                 'error_type' => get_class($e),
                 'parameters' => $parameters,
                ],
                0,
                $e
            );
        }
    }

    /**
     * Resolves a type from the container with optional parameters.
     *
     * Creates an instance of the requested type with any bindings applied and
     * dependencies automatically injected. Additional constructor parameters
     * can be provided.
     *
     * @template T of object
     * @param string|class-string<T> $id Service identifier.
     * @param array<string, mixed> $parameters Additional constructor parameters.
     *
     * @return object The resolved instance.
     *
     * @phpstan-return ( $id is class-string<T> ? T : object )
     *
     * @throws ServiceNotFoundException When the service is not found.
     * @throws CircularDependencyException When circular dependencies are detected.
     * @throws AutowiringException When autowiring fails.
     * @throws InstantiationException When the class cannot be instantiated.
     * @throws InjectionException When injection fails.
     * @throws ResolutionException When resolution fails for other reasons.
     * @throws ContainerException When unexpected errors occur.
     *
     * @example
     *  ```php
     *  // Resolve a service with additional parameters
     *  $user = $container->resolve(UserService::class, [
     *      'role' => 'admin',
     *      'forceCreate' => true
     *  ]);
     *  ```
     */
    public function resolve(string $id, array $parameters = []): object
    {

        // Return existing instance if available.
        if (isset($this->instances[ $id ])) {
            return $this->instances[ $id ];
        }

        // Check for circular dependencies.
        if (isset($this->resolving[ $id ])) {
            throw new CircularDependencyException(
                $id,
                array_merge($this->dependencyChain, [ $id ]),
                [ 'parameters' => $parameters ]
            );
        }

        // Track resolution.
        $this->resolving[ $id ]  = true;
        $this->dependencyChain[] = $id;

        try {
            // Resolve the instance.
            $instance = isset($this->bindings[ $id ])
              ? $this->resolveBinding($id, $parameters)
              : $this->resolveClass($id, $parameters);

            // Apply extensions.
            if (isset($this->extensions[ $id ])) {
                foreach ($this->extensions[ $id ] as $extension) {
                    $instance = $extension($instance, $this);
                }
            }

            // Store shared instances.
            if (isset($this->bindings[ $id ]) && $this->bindings[ $id ]['shared']) {
                $this->instances[ $id ] = $instance;
            }

            // Inject dependencies.
            $this->injectViaPropAttribute($instance);
            $this->injectViaSetters($instance);

            array_pop($this->dependencyChain);
            unset($this->resolving[ $id ]);

            return $instance;
        } catch (ContainerException $e) {
            array_pop($this->dependencyChain);
            unset($this->resolving[ $id ]);

            throw $e;
        }
    }

    /**
     * Injects method dependencies using setter methods.
     *
     * Automatically identifies and calls setter methods (methods starting with 'set')
     * that have a single parameter with a class or interface type hint, injecting
     * the corresponding dependency from the container.
     *
     * @param object $instance The instance to inject into.
     *
     * @throws InjectionException When method injection fails.
     * @throws ContainerException When unexpected errors occur during dependency resolution.
     */
    private function injectViaSetters(object $instance): void
    {
        $reflection = new ReflectionClass($instance);
        $className = $reflection->getName();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            // Only process methods that start with 'set'
            if (! str_starts_with($methodName, 'set')) {
                continue;
            }

            // Methods should have exactly one parameter
            // So each class `dependency` property should have one public setter.
            $params = $method->getParameters();
            if (count($params) !== 1) {
                // Skip such methods as they are not relevant.
                continue;
            }

            $param = $params[0];
            if (! $param->hasType()) {
                continue;
            }

            $type = $param->getType();
            if (! ( $type instanceof ReflectionNamedType ) || $type->isBuiltin()) {
                continue;
            }

            /** @var class-string $dependencyClass */
            $dependencyClass = $type->getName();

            // Skip if parameter is optional and no implementation exists
            if (( $param->isOptional() || $param->allowsNull() ) && ! $this->has($dependencyClass)) {
                continue;
            }

            try {
                // Check for contextual binding first
                if (isset($this->contextualBindings[ $className ][ $dependencyClass ])) {
                    $binding    = $this->contextualBindings[ $className ][ $dependencyClass ];
                    $concrete   = $binding['concrete'];
                    $dependency = is_string($concrete) ? $this->resolveClass($concrete) : $concrete($this, []);
                } else {
                    // Fall back to regular resolution
                    $dependency = $this->resolve($dependencyClass);
                }

                $method->invoke($instance, $dependency);
            } catch (ContainerException $e) {
                // Convert to InjectionException.
                throw new InjectionException(
                    $className,
                    $methodName,
                    'method',
                    sprintf('Failed to resolve method dependencies: %s', $e->getMessage()),
                    $this->dependencyChain,
                    [
                     'original_exception' => get_class($e),
                     'dependencyClass'   => $dependencyClass,
                    ],
                    0,
                    $e
                );

                // @codeCoverageIgnoreStart
            } catch (ReflectionException $e) {
                throw new InjectionException(
                    $className,
                    $methodName,
                    'method',
                    sprintf('Reflection error: %s', $e->getMessage()),
                    $this->dependencyChain,
                    [],
                    0,
                    $e
                );
            } catch (Throwable $e) {
                throw new InjectionException(
                    $className,
                    $methodName,
                    'method',
                    sprintf('Unexpected error: %s', $e->getMessage()),
                    $this->dependencyChain,
                    [
                     'error_type' => get_class($e),
                    ],
                    0,
                    $e
                );
            }
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Injects dependencies into an instance via property attributes.
     *
     * Identifies properties annotated with the Inject attribute and injects
     * the corresponding dependencies from the container.
     *
     * @param object $instance The instance to inject dependencies into.
     *
     * @throws InjectionException When property injection fails due to inaccessible properties or missing type hints.
     * @throws ContainerException When dependency resolution fails.
     */
    private function injectViaPropAttribute(object $instance): void
    {
        $reflection = new ReflectionClass($instance);
        $className = $reflection->getName();

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Inject::class, ReflectionAttribute::IS_INSTANCEOF);

            if (empty($attributes)) {
                continue;
            }

            /** @var Inject $attribute */
            $attribute       = $attributes[0]->newInstance();
            $propertyName    = $property->getName();
            $dependencyClass = $attribute->getDependencyClass();

            if (empty($dependencyClass)) {
                $type = $property->getType();
                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                    $dependencyClass = $type->getName();
                }
            }
            if (empty($dependencyClass)) {
                throw new InjectionException(
                    $className,
                    $propertyName,
                    'property',
                    'No dependency type specified and no type hint available',
                    $this->dependencyChain,
                    [
                     'has_attribute'   => true,
                     'attribute_class' => get_class($attribute),
                    ]
                );
            }

            /** @var class-string $dependencyClass */
            try {
                // Check for contextual binding first.
                if (isset($this->contextualBindings[ $className ][ $dependencyClass ])) {
                    $binding   = $this->contextualBindings[ $className ][ $dependencyClass ];
                    $concrete  = $binding['concrete'];
                    $dependency = is_string($concrete) ? $this->resolveClass($concrete) : $concrete($this, []);
                } else {
                    // Fall back to regular resolution
                    $dependency = $this->resolve($dependencyClass);
                }

                if ($property->isPublic()) {
                    $property->setValue($instance, $dependency);
                } else {
                    throw new InjectionException(
                        $className,
                        $propertyName,
                        'property',
                        sprintf('Property "%s" is not public', $propertyName),
                        $this->dependencyChain,
                        [
                         'dependencyClass' => $dependencyClass,
                        ]
                    );
                }
            } catch (ContainerException $e) {
                if ($e instanceof InjectionException && ! $property->isPublic()) {
                    throw $e;
                }

                throw new InjectionException(
                    $className,
                    $propertyName,
                    'property',
                    sprintf('Failed to resolve property dependencies: %s', $e->getMessage()),
                    $this->dependencyChain,
                    [
                     'original_exception' => get_class($e),
                     'dependencyClass'   => $dependencyClass,
                    ],
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Pass the container to a service provider for registration.
     *
     * Service providers offer a way to organize related container bindings
     * and bootstrapping logic in separate classes.
     *
     * @param class-string<ProviderInterface> $providerClass Service Provider class name.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ContainerException When provider registration fails.
     *
     * @example
     *  ```php
     *  $container->provider(CacheServiceProvider::class);
     *  ```
     */
    public function provider(string $providerClass): static
    {
        try {
            $provider = $this->resolve($providerClass);
            $provider->register();

            return $this;
        } catch (Throwable $e) {
            throw new ContainerException(
                sprintf('Failed to register provider "%s": %s', $providerClass, $e->getMessage()),
                $providerClass,
                $this->dependencyChain,
                [
                 'operation' => 'provider',
                ],
                0,
                $e
            );
        }
    }

    /**
     * Retrieve a service from the container.
     *
     * Implementation of the PSR-11 get method to resolve an entry from the container.
     *
     * @template T of object
     *
     * @param string|class-string<T> $id Identifier of the entry to look for.
     *
     * @return ($id is class-string<T> ? T : object) The resolved entry.
     *
     * @throws ServiceNotFoundException When the service is not found.
     * @throws CircularDependencyException When circular dependencies are detected.
     * @throws AutowiringException When autowiring fails.
     * @throws InstantiationException When the class cannot be instantiated.
     * @throws InjectionException When injection fails.
     * @throws ResolutionException When resolution fails for other reasons.
     * @throws ContainerException When unexpected errors occur.
     *
     * @example
     *  ```php
     *  // Retrieve a service that has been previously bound
     *  $userRepository = $container->get(UserRepositoryInterface::class);
     *
     *  // Retrieve a service with auto-wiring
     *  $logger = $container->get(LoggerInterface::class);
     *  ```
     */
    public function get(string $id)
    {
        return $this->resolve($id);
    }

    /**
     * Binds a singleton implementation.
     *
     * Registers a binding that will return the same instance each time
     * it is resolved from the container.
     *
     * @template T of object
     *
     * @param string|class-string<T> $id Service identifier.
     * @param ( $id is class-string<T> ?
     *          ( null|class-string<T>|(Closure(self, array<string, mixed>): T) ) :
     *          ( null|string|(Closure(self, array<string, mixed>): object) )
     *        ) $implementation Concrete implementation or factory.
     * @param bool $override True to override preregistered implementation if there's any.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ServiceAlreadyBoundException When service is already bound.
     * @throws BindingException When singleton cannot be registered.
     *
     * @example
     *  ```php
     *  // Register a singleton service
     *  $container->singleton(ConfigInterface::class, function() {
     *      return new Config('/path/to/config.json');
     *  });
     *
     *  // Always returns the same config instance
     *  $config1 = $container->get(ConfigInterface::class);
     *  $config2 = $container->get(ConfigInterface::class);
     *  // $config1 === $config2 is true
     *  ```
     */
    public function singleton(string $id, null|string|Closure $implementation = null, bool $override = false): static
    {
        if (empty($implementation)) {
            $implementation = $id;
        }
        return $this->bind($id, $implementation, true, $override);
    }

    /**
     * Resolve all services under a specific tag.
     *
     * Retrieves and instantiates all services that have been registered
     * under the given tag.
     *
     * @template T of object
     * @param string|class-string<T> $tag Tag name.
     *
     * @return ( $tag is class-string<T> ? array<T> : array<object> ) Array of resolved services.
     *
     * @throws ServiceNotFoundException When any tagged service is not found.
     * @throws CircularDependencyException When circular dependencies are detected.
     * @throws AutowiringException When autowiring fails.
     * @throws ResolutionException When resolution fails for other reasons.
     * @throws ContainerException When resolution fails for other reasons.
     *
     * @example
     *  ```php
     *  // Tag multiple services
     *  $container->tag('payment', [
     *      StripePayment::class,
     *      PayPalPayment::class
     *  ]);
     *
     *  // Resolve all tagged payment services
     *  $paymentMethods = $container->resolveTagged('payment');
     *  ```
     */
    public function resolveTagged(string $tag): array
    {
        if (! isset($this->tags[ $tag ])) {
            return [];
        }

        $resolved = [];

        foreach ($this->tags[$tag] as $id) {
            $resolved[] = $this->resolve($id);
        }

        return $resolved;
    }

    /**
     * Tag several services under a common tag.
     *
     * Tags provide a way to group related services so they can be resolved
     * collectively later.
     *
     * @template T of object
     * @param string|class-string<T> $tag Tag name.
     * @param ( $tag is class-string<T> ?
     *          array<class-string<T>> :
     *          array<string>
     *        ) $ids Array of service identifiers to tag.
     * @param bool $merge Whether to merge the services on given tag or replace them by the given service ids list.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ServiceNotFoundException When any of the services cannot be found.
     * @throws ContainerException When the tag operation fails.
     *
     * @example
     *  ```php
     *  // Tag multiple services
     *  $container->tag('logging', [
     *      FileLogger::class,
     *      DatabaseLogger::class
     *  ]);
     *
     *  // Add another logger to the tag
     *  $container->tag('logging', [ConsoleLogger::class]);
     *  ```
     */
    public function tag(string $tag, array $ids, bool $merge = true): static
    {
        if (empty($tag)) {
            throw new ContainerException(
                'Tag name cannot be empty',
                'unknown',
                $this->dependencyChain,
                [
                 'services' => $ids,
                 'merge' => $merge,
                ]
            );
        }

        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return $this;
        }

        if (! isset($this->tags[ $tag ])) {
            $this->tags[ $tag ] = [];
        }

        foreach ($ids as $id) {
            if (! $this->has($id)) {
                throw new ServiceNotFoundException(
                    $id,
                    $this->dependencyChain,
                    [
                     'tag'       => $tag,
                    'operation' => 'tag'
                    ]
                );
            }
        }


        $this->tags[ $tag ] = $merge
          ? array_values(array_unique([...$this->tags[ $tag ], ...$ids]))
          : $ids;


        return $this;
    }

    /**
     * Untag service(s) under a common tag.
     *
     * Removes one or more services from a specific tag.
     *
     * @template T of object
     * @param string|class-string<T> $tag Tag name.
     * @param ( $tag is class-string<T> ?
     *          array<class-string<T>>|class-string<T> :
     *          array<string>|string
     *        ) $ids Service identifier(s) to untag.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @example
     *  ```php
     *  // Remove specific services from a tag
     *  $container->untag('logging', [FileLogger::class]);
     *
     *  // Remove all services from a tag
     *  $container->untag('logging', [
     *      FileLogger::class,
     *      DatabaseLogger::class,
     *      ConsoleLogger::class
     *  ]);
     *  ```
     */
    public function untag(string $tag, array|string $ids): static
    {
        $ids = is_array($ids) ? $ids : [ $ids ];

        if (! isset($this->tags[ $tag ])) {
            return $this;
        }

        // Use array_values to reindex array after filtering.
        $this->tags[ $tag ] = array_values(
            array_filter(
                $this->tags[ $tag ],
                fn($service) => ! in_array($service, $ids, true)
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        /** @phpstan-var class-string $id */
        return $this->hasBinding($id) || class_exists($id);
    }

    /**
     * Extend an existing binding.
     *
     * Allows decorating or modifying a service after it has been resolved
     * from the container.
     *
     * @template T of object
     * @param string|class-string<T> $id Service identifier.
     * @param ( $id is class-string<T> ?
     *          ( Closure(T, self): T ) :
     *          ( Closure(object, self): object )
     *        ) $extension Extension callback.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ServiceNotFoundException When the service is not found.
     * @throws ContainerException When extending fails.
     *
     * @example
     *  ```php
     *  // Extend an existing service to add additional functionality
     *  $container->extend(LoggerInterface::class, function($logger, $container) {
     *      // Add additional logging capabilities
     *      $logger->addHandler(new SentryLogHandler());
     *      return $logger;
     *  });
     *  ```
     */
    public function extend(string $id, Closure $extension): static
    {
        if (! $this->has($id)) {
            throw new ServiceNotFoundException(
                $id,
                $this->dependencyChain,
                [ 'operation' => 'extend' ]
            );
        }

        if (! isset($this->extensions[ $id ])) {
            $this->extensions[ $id ] = [];
        }

        /** @phpstan-var (Closure(object, Container): object) $extension */

        $this->extensions[ $id ][] = $extension;

        return $this;
    }

    /**
     * Remove a binding from the container.
     *
     * Unregisters a previously bound service identifier from the container.
     *
     * @param string $id Service identifier.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ServiceNotFoundException When the service is not found.
     * @throws ContainerException When unbinding fails.
     *
     * @example
     *  ```php
     *  // Remove a specific service binding
     *  $container->unbind(UserRepositoryInterface::class);
     *  ```
     */
    public function unbind(string $id): static
    {
        if (! $this->hasBinding($id)) {
            throw new ServiceNotFoundException(
                $id,
                $this->dependencyChain,
                [ 'operation' => 'unbind' ]
            );
        }

        unset(
            $this->bindings[ $id ],
            $this->instances[ $id ],
            $this->extensions[ $id ]
        );

        // Remove any contextual bindings where this is the implementation
        foreach ($this->contextualBindings as $concrete => $bindings) {
            foreach ($bindings as $abstract => $binding) {
                // If there's a string implementation that matches the ID we're unbinding
                if (
                    is_string($binding['concrete']) &&
                    $binding['concrete'] === $id
                ) {
                    unset($this->contextualBindings[ $concrete ][ $abstract ]);
                }
            }

            // Clean up empty arrays
            if (empty($this->contextualBindings[ $concrete ])) {
                unset($this->contextualBindings[ $concrete ]);
            }
        }

        return $this;
    }

    /**
     * Remove contextual bindings for a concrete class.
     *
     * @param string $concrete The concrete class.
     * @param string|null $abstract Optional. The abstract type to remove. If null, removes all.
     *
     * @return static
     *
     * @example
     *  ```php
     *  // Remove all contextual bindings for a specific class
     *  $container->forgetWhen(UserService::class);
     *
     *  // Remove a specific contextual binding for a class
     *  $container->forgetWhen(UserService::class, LoggerInterface::class);
     *  ```
     */
    public function forgetWhen(string $concrete, ?string $abstract = null): static
    {
        if (isset($this->contextualBindings[$concrete])) {
            if ($abstract === null) {
                unset($this->contextualBindings[$concrete]);
            } elseif (isset($this->contextualBindings[$concrete][$abstract])) {
                unset($this->contextualBindings[$concrete][$abstract]);

                if (empty($this->contextualBindings[$concrete])) {
                    unset($this->contextualBindings[$concrete]); // @codeCoverageIgnore
                }
            }
        }

        return $this;
    }
}
