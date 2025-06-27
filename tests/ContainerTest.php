<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use ReflectionClass;
use WPTechnix\DI\Container;
use WPTechnix\DI\Exceptions\AutowiringException;
use WPTechnix\DI\Exceptions\BindingException;
use WPTechnix\DI\Exceptions\CircularDependencyException;
use WPTechnix\DI\Exceptions\ContainerException;
use WPTechnix\DI\Exceptions\InjectionException;
use WPTechnix\DI\Exceptions\InstantiationException;
use WPTechnix\DI\Exceptions\ResolutionException;
use WPTechnix\DI\Exceptions\ServiceAlreadyBoundException;
use WPTechnix\DI\Exceptions\ServiceNotFoundException;
use WPTechnix\DI\Tests\Fixture\AbstractClass;
use WPTechnix\DI\Tests\Fixture\AnotherImplementation;
use WPTechnix\DI\Tests\Fixture\TestInterface;
use WPTechnix\DI\Tests\Fixture\AnotherInterface;
use WPTechnix\DI\Tests\Fixture\CircularA;
use WPTechnix\DI\Tests\Fixture\CircularB;
use WPTechnix\DI\Tests\Fixture\NestedDependency;
use WPTechnix\DI\Tests\Fixture\NonExistentServicePropertyInjection;
use WPTechnix\DI\Tests\Fixture\ServicePrivateConstructor;
use WPTechnix\DI\Tests\Fixture\ServiceProvider;
use WPTechnix\DI\Tests\Fixture\ServiceWithAbstractClassDeps;
use WPTechnix\DI\Tests\Fixture\ServiceWithCircularDependencyViaMethodInjection;
use WPTechnix\DI\Tests\Fixture\ServiceWithDependency;
use WPTechnix\DI\Tests\Fixture\ServiceWithImproperPropertyInjection;
use WPTechnix\DI\Tests\Fixture\ServiceWithMethodInjection;
use WPTechnix\DI\Tests\Fixture\ServiceWithNonExistentDependencyViaMethodInjection;
use WPTechnix\DI\Tests\Fixture\ServiceWithNullableDefaultParam;
use WPTechnix\DI\Tests\Fixture\ServiceWithNullableParam;
use WPTechnix\DI\Tests\Fixture\ServiceWithoutClassOrInterfaceTypehint;
use WPTechnix\DI\Tests\Fixture\ServiceWithParameters;
use WPTechnix\DI\Tests\Fixture\ServiceWithPrimitiveParam;
use WPTechnix\DI\Tests\Fixture\ServiceWithPrivatePropertyInjection;
use WPTechnix\DI\Tests\Fixture\ServiceWithPropertyInjection;
use WPTechnix\DI\Tests\Fixture\ServiceWithUnionType;
use WPTechnix\DI\Tests\Fixture\SimpleImplementation;
use WPTechnix\DI\Tests\Fixture\TestTrait;
use WPTechnix\DI\Tests\Fixture\ThrowingService;
use WPTechnix\DI\Tests\Fixture\ThrowingServiceProvider;
use WPTechnix\DI\Tests\Fixture\ValueImplementation;

/**
 * Container Tests
 *
 * @covers \WPTechnix\DI\Container
 * @covers \WPTechnix\DI\Attributes\Inject
 * @covers \WPTechnix\DI\Exceptions\ContainerException
 * @covers \WPTechnix\DI\Exceptions\BindingException
 * @covers \WPTechnix\DI\Exceptions\InstantiationException
 * @covers \WPTechnix\DI\Exceptions\ServiceAlreadyBoundException
 * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
 * @covers \WPTechnix\DI\Exceptions\AutowiringException
 * @covers \WPTechnix\DI\Exceptions\ResolutionException
 * @covers \WPTechnix\DI\Exceptions\InjectionException
 * @covers \WPTechnix\DI\Exceptions\CircularDependencyException
 */
class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * Set up a fresh container for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    /**
     * Helper method to setup common interface bindings.
     */
    private function setupInterfaceBindings(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind(AnotherInterface::class, ValueImplementation::class);
        $this->container->bind(AbstractClass::class, SimpleImplementation::class);
    }

    /**
     * Helper method to access private properties for testing internal state.
     */
    private function getPrivateProperty(object $object, string $property)
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    /**
     * Helper method to set private properties for testing.
     */
    private function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    //------------------------------------------------------------------
    // Constructor and Basic Container Tests
    //------------------------------------------------------------------

    /**
     * Tests that constructor registers Container.
     *
     * @covers \WPTechnix\DI\Container::__construct
     */
    public function testConstructorRegistersContainer(): void
    {
        $this->assertTrue($this->container->hasBinding(Container::class));
        $this->assertSame($this->container, $this->container->get(Container::class));
    }

    //------------------------------------------------------------------
    // Instance Registration Tests
    //------------------------------------------------------------------

    /**
     * Tests instance registration with a simple object.
     *
     * @covers \WPTechnix\DI\Container::instance
     * @covers \WPTechnix\DI\Container::ensureNotBound
     */
    public function testInstanceRegistrationWithObject(): void
    {
        $object = new stdClass();
        $this->container->instance('std', $object);

        $this->assertTrue($this->container->hasBinding('std'));
        $this->assertSame($object, $this->container->get('std'));
    }

    /**
     * Tests instance registration with an interface.
     *
     * @covers \WPTechnix\DI\Container::instance
     * @covers \WPTechnix\DI\Container::ensureNotBound
     */
    public function testInstanceRegistrationWithInterface(): void
    {
        $implementation = new SimpleImplementation();
        $this->container->instance(TestInterface::class, $implementation);

        $this->assertTrue($this->container->hasBinding(TestInterface::class));
        $this->assertSame($implementation, $this->container->get(TestInterface::class));
    }

    /**
     * Tests that instance registration fails when service is already bound.
     *
     * @covers \WPTechnix\DI\Container::instance
     * @covers \WPTechnix\DI\Container::ensureNotBound
     * @covers \WPTechnix\DI\Exceptions\ServiceAlreadyBoundException
     */
    public function testInstanceRegistrationFailsWhenAlreadyBound(): void
    {
        $this->container->instance('service', new stdClass());

        $this->expectException(ServiceAlreadyBoundException::class);
        $this->container->instance('service', new stdClass());
    }

    //------------------------------------------------------------------
    // Binding Tests
    //------------------------------------------------------------------

    /**
     * Tests binding a class to an interface.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     */
    public function testBindClassToInterface(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        $instance = $this->container->get(TestInterface::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
        $this->assertEquals('Simple', $instance->getName());
    }

    /**
     * Tests binding a closure.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     */
    public function testBindClosure(): void
    {
        $this->container->bind('service', function () {
            return new SimpleImplementation();
        });

        $instance = $this->container->get('service');
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
    }

    /**
     * Tests bind shared behavior.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testBindSharedBehavior(): void
    {
        // Non-shared binding (default)
        $this->container->bind('non-shared', SimpleImplementation::class);
        $instance1 = $this->container->get('non-shared');
        $instance2 = $this->container->get('non-shared');
        $this->assertNotSame($instance1, $instance2, 'Non-shared bindings should return different instances');

        // Shared binding
        $this->container->bind('shared', SimpleImplementation::class, true);
        $instance3 = $this->container->get('shared');
        $instance4 = $this->container->get('shared');
        $this->assertSame($instance3, $instance4, 'Shared bindings should return the same instance');
    }

    /**
     * Tests bind with override succeeds.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     */
    public function testBindWithOverrideSucceeds(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Override should succeed
        $this->container->bind(TestInterface::class, AnotherImplementation::class, false, true);

        $instance = $this->container->get(TestInterface::class);
        $this->assertInstanceOf(AnotherImplementation::class, $instance);
    }

    /**
     * Tests bind without override fails.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     * @covers \WPTechnix\DI\Exceptions\ServiceAlreadyBoundException
     */
    public function testBindWithoutOverrideFails(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        $this->expectException(ServiceAlreadyBoundException::class);
        $this->container->bind(TestInterface::class, AnotherImplementation::class);
    }

    /**
     * Tests bind with non-existent implementation fails.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     * @covers \WPTechnix\DI\Exceptions\BindingException
     */
    public function testBindWithNonExistentImplementationFails(): void
    {
        $this->expectException(BindingException::class);
        $this->container->bind(TestInterface::class, 'NonExistentClass');
    }

    /**
     * Tests bind with a throwing implementation.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::resolveBinding
     * @covers \WPTechnix\DI\Exceptions\ResolutionException
     */
    public function testBindWithThrowingImplementation(): void
    {
        $this->container->bind('throwing', function () {
            throw new RuntimeException('Implementation throws');
        });

        $this->expectException(ResolutionException::class);
        $this->container->get('throwing');
    }

    //------------------------------------------------------------------
    // Factory Tests
    //------------------------------------------------------------------

    /**
     * Tests basic factory registration.
     *
     * @covers \WPTechnix\DI\Container::factory
     * @covers \WPTechnix\DI\Container::bind
     */
    public function testFactoryRegistrationBasic(): void
    {
        $this->container->factory('factory', function () {
            return new stdClass();
        });

        $instance1 = $this->container->get('factory');
        $instance2 = $this->container->get('factory');

        $this->assertInstanceOf(stdClass::class, $instance1);
        $this->assertInstanceOf(stdClass::class, $instance2);
        $this->assertNotSame($instance1, $instance2, 'Factory should create new instances');
    }

    /**
     * Tests factory with parameters.
     *
     * @covers \WPTechnix\DI\Container::factory
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testFactoryWithParameters(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->factory('parameterized-factory', function ($container, $parameters) {
            $service = $container->resolve(ServiceWithParameters::class, $parameters);
            return $service;
        });

        $instance = $this->container->resolve('parameterized-factory', [
          'name' => 'Test',
          'value' => 42
        ]);

        $this->assertInstanceOf(ServiceWithParameters::class, $instance);
        $this->assertEquals('Test', $instance->getName());
        $this->assertEquals(42, $instance->getValue());
    }

    /**
     * Tests factory with override succeeds.
     *
     * @covers \WPTechnix\DI\Container::factory
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     */
    public function testFactoryWithOverrideSucceeds(): void
    {
        $this->container->factory('service', function () {
            return new SimpleImplementation();
        });

        // Override should succeed
        $this->container->factory('service', function () {
            return new AnotherImplementation();
        }, true);

        $instance = $this->container->get('service');
        $this->assertInstanceOf(AnotherImplementation::class, $instance);
    }

    /**
     * Tests factory without override fails.
     *
     * @covers \WPTechnix\DI\Container::factory
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     * @covers \WPTechnix\DI\Exceptions\ServiceAlreadyBoundException
     */
    public function testFactoryWithoutOverrideFails(): void
    {
        $this->container->factory('service', function () {
            return new SimpleImplementation();
        });

        $this->expectException(ServiceAlreadyBoundException::class);
        $this->container->factory('service', function () {
            return new AnotherImplementation();
        });
    }

    //------------------------------------------------------------------
    // Singleton Tests
    //------------------------------------------------------------------

    /**
     * Tests singleton with class implementation.
     *
     * @covers \WPTechnix\DI\Container::singleton
     * @covers \WPTechnix\DI\Container::bind
     */
    public function testSingletonWithClassImplementation(): void
    {
        $this->container->singleton(TestInterface::class, SimpleImplementation::class);

        $instance = $this->container->get(TestInterface::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
    }

    /**
     * Tests singleton with closure implementation.
     *
     * @covers \WPTechnix\DI\Container::singleton
     * @covers \WPTechnix\DI\Container::bind
     */
    public function testSingletonWithClosureImplementation(): void
    {
        $this->container->singleton(TestInterface::class, function () {
            return new SimpleImplementation();
        });

        $instance = $this->container->get(TestInterface::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
    }

    /**
     * Tests singleton with null implementation.
     *
     * @covers \WPTechnix\DI\Container::singleton
     * @covers \WPTechnix\DI\Container::bind
     */
    public function testSingletonWithNullImplementation(): void
    {
        $this->container->singleton(SimpleImplementation::class);

        $instance = $this->container->get(SimpleImplementation::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
    }

    /**
     * Tests singleton with override succeeds.
     *
     * @covers \WPTechnix\DI\Container::singleton
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     */
    public function testSingletonWithOverrideSucceeds(): void
    {
        $this->container->singleton(TestInterface::class, SimpleImplementation::class);

        // Override should succeed
        $this->container->singleton(TestInterface::class, AnotherImplementation::class, true);

        $instance = $this->container->get(TestInterface::class);
        $this->assertInstanceOf(AnotherImplementation::class, $instance);
    }

    /**
     * Tests singleton without override fails.
     *
     * @covers \WPTechnix\DI\Container::singleton
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::ensureNotBound
     * @covers \WPTechnix\DI\Exceptions\ServiceAlreadyBoundException
     */
    public function testSingletonWithoutOverrideFails(): void
    {
        $this->container->singleton(TestInterface::class, SimpleImplementation::class);

        $this->expectException(ServiceAlreadyBoundException::class);
        $this->container->singleton(TestInterface::class, AnotherImplementation::class);
    }

    /**
     * Tests singleton creates same instance.
     *
     * @covers \WPTechnix\DI\Container::singleton
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testSingletonCreatesSameInstance(): void
    {
        $this->container->singleton(TestInterface::class, SimpleImplementation::class);

        $instance1 = $this->container->get(TestInterface::class);
        $instance2 = $this->container->get(TestInterface::class);

        $this->assertSame($instance1, $instance2, 'Singleton should return the same instance');
    }

    //------------------------------------------------------------------
    // Has and Has_Binding Tests
    //------------------------------------------------------------------

    /**
     * Tests has_binding returns true for bound service.
     *
     * @covers \WPTechnix\DI\Container::hasBinding
     */
    public function testHasBindingReturnsTrueForBoundService(): void
    {
        $this->container->instance('service', new stdClass());
        $this->assertTrue($this->container->hasBinding('service'));
    }

    /**
     * Tests has_binding returns false for unbound service.
     *
     * @covers \WPTechnix\DI\Container::hasBinding
     */
    public function testHasBindingReturnsFalseForUnboundService(): void
    {
        $this->assertFalse($this->container->hasBinding('non-existent'));
    }

    /**
     * Tests has_binding works with different binding methods.
     *
     * @covers \WPTechnix\DI\Container::hasBinding
     */
    public function testHasBindingWorksWithDifferentBindingMethods(): void
    {
        $this->container->instance('instance-service', new stdClass());
        $this->container->bind('bind-service', stdClass::class);
        $this->container->singleton('singleton-service', stdClass::class);
        $this->container->factory('factory-service', fn() => new stdClass());

        $this->assertTrue($this->container->hasBinding('instance-service'));
        $this->assertTrue($this->container->hasBinding('bind-service'));
        $this->assertTrue($this->container->hasBinding('singleton-service'));
        $this->assertTrue($this->container->hasBinding('factory-service'));
    }

    /**
     * Tests has returns true for bound service.
     *
     * @covers \WPTechnix\DI\Container::has
     * @covers \WPTechnix\DI\Container::hasBinding
     */
    public function testHasReturnsTrueForBoundService(): void
    {
        $this->container->bind('service', function () {
            return new stdClass();
        });

        $this->assertTrue($this->container->has('service'));
    }

    /**
     * Tests has returns true for unbound existing class.
     *
     * @covers \WPTechnix\DI\Container::has
     */
    public function testHasReturnsTrueForUnboundExistingClass(): void
    {
        $this->assertTrue($this->container->has(SimpleImplementation::class));
    }

    /**
     * Tests has returns false for non-existent service.
     *
     * @covers \WPTechnix\DI\Container::has
     */
    public function testHasReturnsFalseForNonExistentService(): void
    {
        $this->assertFalse($this->container->has('non-existent-service'));
        $this->assertFalse($this->container->has('NonExistentClass'));
    }

    //------------------------------------------------------------------
    // Reset Tests
    //------------------------------------------------------------------

    /**
     * Tests that reset clears all bindings.
     *
     * @covers \WPTechnix\DI\Container::reset
     */
    public function testResetClearsAllBindings(): void
    {
        $this->container->instance('service', new stdClass());
        $this->container->bind('another', stdClass::class);

        $this->container->reset();

        $this->assertFalse($this->container->hasBinding('service'));
        $this->assertFalse($this->container->hasBinding('another'));
    }

    /**
     * Tests that reset preserves Container binding.
     *
     * @covers \WPTechnix\DI\Container::reset
     */
    public function testResetPreservesContainerBinding(): void
    {
        $this->container->reset();
        $this->assertTrue($this->container->hasBinding(Container::class));
        $this->assertSame($this->container, $this->container->get(Container::class));
    }

    /**
     * Tests that reset clears tags and extensions.
     *
     * @covers \WPTechnix\DI\Container::reset
     * @covers \WPTechnix\DI\Container::tag
     * @covers \WPTechnix\DI\Container::extend
     * @covers \WPTechnix\DI\Container::resolveTagged
     */
    public function testResetClearsTagsAndExtensions(): void
    {
        // Setup services, tags, and extensions
        $this->container->instance('service1', new stdClass());
        $this->container->instance('service2', new stdClass());
        $this->container->tag('test-tag', ['service1', 'service2']);

        $this->container->extend('service1', function ($service) {
            return $service;
        });

        // Verify setup
        $this->assertCount(2, $this->container->resolveTagged('test-tag'));

        // Reset and verify
        $this->container->reset();

        // Tag should be gone
        $this->assertCount(0, $this->container->resolveTagged('test-tag'));
    }

    /**
     * Tests reset clears the dependency chain and resolving array.
     *
     * @covers \WPTechnix\DI\Container::reset
     */
    public function testResetClearsDependencyChainAndResolving(): void
    {
        // Set some values in private properties
        $this->setPrivateProperty($this->container, 'resolving', ['service' => true]);
        $this->setPrivateProperty($this->container, 'dependencyChain', ['service']);

        // Reset
        $this->container->reset();

        // Verify empty
        $this->assertEmpty($this->getPrivateProperty($this->container, 'resolving'));
        $this->assertEmpty($this->getPrivateProperty($this->container, 'dependencyChain'));
    }

    //------------------------------------------------------------------
    // Resolve Tests
    //------------------------------------------------------------------

    /**
     * Tests resolving a simple class.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     */
    public function testResolveSimpleClass(): void
    {
        $instance = $this->container->resolve(SimpleImplementation::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
    }

    /**
     * Tests resolving a class with dependencies.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testResolveClassWithDependencies(): void
    {
        $this->setupInterfaceBindings();

        $instance = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(ServiceWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleImplementation::class, $instance->getDependency());
    }

    /**
     * Tests resolving a class with abstract class dependencies.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testResolveClassWithAbstractClassDependencies(): void
    {
        $this->setupInterfaceBindings();

        $instance = $this->container->resolve(ServiceWithAbstractClassDeps::class);
        $this->assertInstanceOf(ServiceWithAbstractClassDeps::class, $instance);
        $this->assertInstanceOf(SimpleImplementation::class, $instance->getDependency());
    }

    /**
     * Tests resolving a class with deeply nested dependencies.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testResolveWithDeeplyNestedDependencies(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        $instance = $this->container->resolve(NestedDependency::class);

        $this->assertInstanceOf(NestedDependency::class, $instance);
        $this->assertInstanceOf(ServiceWithDependency::class, $instance->getService());
        $this->assertInstanceOf(SimpleImplementation::class, $instance->getService()->getDependency());
    }

    /**
     * Tests resolving a class without passing required dependency fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     * @covers \WPTechnix\DI\Exceptions\AutowiringException
     */
    public function testResolveClassWithoutPassingRequiredDependencyFails(): void
    {
        $this->expectException(AutowiringException::class);
        $this->container->resolve(ServiceWithDependency::class);
    }

    /**
     * Tests resolving a class without passing required abstract dependency fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     * @covers \WPTechnix\DI\Exceptions\AutowiringException
     */
    public function testResolveClassWithoutPassingRequiredAbstractDependencyFails(): void
    {
        $this->expectException(AutowiringException::class);
        $this->container->resolve(ServiceWithAbstractClassDeps::class);
    }

    /**
     * Tests resolving with constructor parameters.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testResolveWithConstructorParameters(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $instance = $this->container->resolve(ServiceWithParameters::class, [
          'name' => 'Test',
          'value' => 42,
          'flag' => true,
          'options' => ['key' => 'value']
        ]);

        $this->assertEquals('Test', $instance->getName());
        $this->assertEquals(42, $instance->getValue());
        $this->assertTrue($instance->isServiceSet());
        $this->assertTrue($instance->getFlag());
        $this->assertEquals(['key' => 'value'], $instance->getOptions());
    }

    /**
     * Tests resolving with constructor missing parameters.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     * @covers \WPTechnix\DI\Exceptions\AutowiringException
     */
    public function testResolveWithConstructorMissingParameters(): void
    {
        $this->expectException(AutowiringException::class);
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->resolve(ServiceWithParameters::class);
    }

    /**
     * Tests resolving with default parameters.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testResolveWithDefaultParameters(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Only provide required parameters
        $instance = $this->container->resolve(ServiceWithParameters::class, [
          'name' => 'Test'
        ]);

        $this->assertEquals('Test', $instance->getName());
        $this->assertEquals(0, $instance->getValue()); // Default
        $this->assertFalse($instance->getFlag()); // Default
        $this->assertNull($instance->getOptions()); // Default
    }

    /**
     * Tests resolving with nullable parameters.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testResolveWithNullableParameters(): void
    {
        // No binding for TestInterface, but param is nullable
        $instance = $this->container->resolve(ServiceWithNullableParam::class);
        $this->assertNull($instance->getDependency());

        // With binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $instance = $this->container->resolve(ServiceWithNullableParam::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance->getDependency());
    }

    /**
     * Tests resolving with nullable default parameters.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testResolveWithNullableDefaultParameters(): void
    {
        // No binding for TestInterface, but param is nullable with default
        $instance = $this->container->resolve(ServiceWithNullableDefaultParam::class);
        $this->assertNull($instance->getDependency());

        // With binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $instance = $this->container->resolve(ServiceWithNullableDefaultParam::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance->getDependency());
    }

    /**
     * Tests resolving with private constructor.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Exceptions\InstantiationException
     */
    public function testResolveWithPrivateConstructor(): void
    {
        $this->expectException(InstantiationException::class);
        $this->container->resolve(ServicePrivateConstructor::class);
    }

    /**
     * Tests resolving with circular dependency fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Exceptions\CircularDependencyException
     */
    public function testResolveWithCircularDependencyFails(): void
    {
        $this->expectException(CircularDependencyException::class);

        $this->container->bind(CircularA::class, CircularA::class);
        $this->container->bind(CircularB::class, CircularB::class);

        $this->container->resolve(CircularA::class);
    }

    /**
     * Tests resolving non-existent class fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testResolveNonExistentClassFails(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->resolve('NonExistentClass');
    }

    /**
     * Tests resolving interface fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testResolveInterfaceFails(): void
    {
        // Service Not found thrown because TestInterface is not a class failing class_exists().
        $this->expectException(ServiceNotFoundException::class);
        $this->container->resolve(TestInterface::class);
    }

    /**
     * Tests resolving trait fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testResolveTraitFails(): void
    {
        // Service Not found thrown because TestTrait is not a class failing class_exists().
        $this->expectException(ServiceNotFoundException::class);
        $this->container->resolve(TestTrait::class);
    }

    /**
     * Tests resolving abstract class fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Exceptions\InstantiationException
     */
    public function testResolveAbstractClassFails(): void
    {
        $this->expectException(InstantiationException::class);
        $this->container->resolve(AbstractClass::class);
    }

    /**
     * Tests resolving with union type fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     * @covers \WPTechnix\DI\Exceptions\AutowiringException
     */
    public function testResolveWithUnionTypeFails(): void
    {
        $this->expectException(AutowiringException::class);
        $this->container->resolve(ServiceWithUnionType::class);
    }

    /**
     * Tests resolving with missing type hint fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     * @covers \WPTechnix\DI\Exceptions\AutowiringException
     */
    public function testResolveWithMissingTypehintFails(): void
    {
        $this->expectException(AutowiringException::class);
        $this->container->resolve(ServiceWithoutClassOrInterfaceTypehint::class);
    }

    /**
     * Tests resolving with primitive parameter fails.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     * @covers \WPTechnix\DI\Exceptions\AutowiringException
     */
    public function testResolveWithPrimitiveParameterFails(): void
    {
        $this->expectException(AutowiringException::class);
        $this->container->resolve(ServiceWithPrimitiveParam::class);
    }

    /**
     * Tests resolving class that throws during instantiation.
     *
     * @covers \WPTechnix\DI\Container::resolve
     * @covers \WPTechnix\DI\Container::resolveClass
     * @covers \WPTechnix\DI\Exceptions\ContainerException
     */
    public function testResolveClassThatThrowsDuringInstantiation(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->resolve(ThrowingService::class);
    }

    //------------------------------------------------------------------
    // Get Tests
    //------------------------------------------------------------------

    /**
     * Tests getting a bound service.
     *
     * @covers \WPTechnix\DI\Container::get
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testGetBoundService(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        $instance = $this->container->get(TestInterface::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
    }

    /**
     * Tests get behavior with unbound classes.
     *
     * @covers \WPTechnix\DI\Container::get
     * @covers \WPTechnix\DI\Container::hasBinding
     */
    public function testGetAutoRegistersUnboundClass(): void
    {
        // SimpleImplementation is not bound
        $this->assertFalse($this->container->hasBinding(SimpleImplementation::class));

        // Get resolves the class without error
        $instance = $this->container->get(SimpleImplementation::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);

        // The container does NOT auto-register classes, so has_binding should still be false
        $this->assertFalse($this->container->hasBinding(SimpleImplementation::class));

        // Each get call creates a new instance (not shared)
        $instance2 = $this->container->get(SimpleImplementation::class);
        $this->assertNotSame($instance, $instance2);
    }

    /**
     * Tests get with non-existent class fails.
     *
     * @covers \WPTechnix\DI\Container::get
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testGetNonExistentClassFails(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->get('NonExistentClass');
    }

    //------------------------------------------------------------------
    // Property Injection Tests
    //------------------------------------------------------------------

    /**
     * Tests property injection.
     *
     * @covers \WPTechnix\DI\Container::injectViaPropAttribute
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testPropertyInjection(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind(AnotherInterface::class, ValueImplementation::class);

        $instance = $this->container->get(ServiceWithPropertyInjection::class);

        // Public property injection
        $this->assertInstanceOf(SimpleImplementation::class, $instance->publicDependency);
        $this->assertEquals('Simple', $instance->publicDependency->getName());

        // Property with explicit dependency class in attribute
        $this->assertInstanceOf(AnotherImplementation::class, $instance->getExplicitDependency());
        $this->assertEquals('Another', $instance->getExplicitDependency()->getName());
    }

    /**
     * Tests improper property injection.
     *
     * @covers \WPTechnix\DI\Container::injectViaPropAttribute
     * @covers \WPTechnix\DI\Exceptions\InjectionException
     */
    public function testImproperPropertyInjection(): void
    {
        $this->expectException(InjectionException::class);
        $this->container->resolve(ServiceWithImproperPropertyInjection::class);
    }

    /**
     * Tests private property injection fails.
     *
     * @covers \WPTechnix\DI\Container::injectViaPropAttribute
     * @covers \WPTechnix\DI\Exceptions\InjectionException
     */
    public function testPrivatePropertyInjection(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        $this->expectException(InjectionException::class);
        $this->container->resolve(ServiceWithPrivatePropertyInjection::class);
    }

    /**
     * Tests property injection with non-existent service.
     *
     * @covers \WPTechnix\DI\Container::injectViaPropAttribute
     * @covers \WPTechnix\DI\Exceptions\InjectionException
     */
    public function testNonExistentServicePropertyInjection(): void
    {
        $this->expectException(InjectionException::class);
        $this->container->resolve(NonExistentServicePropertyInjection::class);
    }

    //------------------------------------------------------------------
    // Method Injection Tests
    //------------------------------------------------------------------

    /**
     * Tests method injection.
     *
     * @covers \WPTechnix\DI\Container::injectViaSetters
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testMethodInjection(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind(AnotherInterface::class, ValueImplementation::class);

        $instance = $this->container->resolve(ServiceWithMethodInjection::class);
        $dependencies = $instance->getDependencies();

        // Method dependency injection
        $this->assertInstanceOf(SimpleImplementation::class, $dependencies['test']);
        $this->assertInstanceOf(ValueImplementation::class, $dependencies['another']);
    }

    /**
     * Tests method injection with optional parameters.
     *
     * @covers \WPTechnix\DI\Container::injectViaSetters
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testMethodInjectionWithOptionalParameters(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        // Intentionally not binding AnotherInterface

        $instance = $this->container->get(ServiceWithMethodInjection::class);
        $dependencies = $instance->getDependencies();

        // Required parameter should be injected
        $this->assertInstanceOf(SimpleImplementation::class, $dependencies['test']);

        // Optional parameter should be null
        $this->assertNull($dependencies['another'] ?? null);
    }

    /**
     * Tests method injection skips methods with multiple parameters.
     *
     * @covers \WPTechnix\DI\Container::injectViaSetters
     */
    public function testMethodInjectionSkipsMultipleParams(): void
    {
        $this->setupInterfaceBindings();

        $instance = $this->container->resolve(ServiceWithMethodInjection::class);
        $dependencies = $instance->getDependencies();

        // Method with multiple parameters should be skipped
        $this->assertArrayNotHasKey('prop', $dependencies);
    }

    /**
     * Test method injection with non-existent dependency.
     *
     * @covers \WPTechnix\DI\Container::injectViaSetters
     * @covers \WPTechnix\DI\Exceptions\InjectionException
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testMethodInjectionWithNonExistentDependency(): void
    {
        $this->expectException(InjectionException::class);
        $this->container->resolve(ServiceWithNonExistentDependencyViaMethodInjection::class);
    }

    /**
     * Test method injection with circular dependency.
     *
     * @covers \WPTechnix\DI\Container::injectViaSetters
     * @covers \WPTechnix\DI\Exceptions\InjectionException
     * @covers \WPTechnix\DI\Exceptions\CircularDependencyException
     */
    public function testMethodInjectionCircularDependency(): void
    {
        $this->container->bind(CircularA::class, CircularA::class);
        $this->container->bind(CircularB::class, CircularB::class);

        $this->expectException(InjectionException::class);
        $this->container->resolve(ServiceWithCircularDependencyViaMethodInjection::class);
    }

    //------------------------------------------------------------------
    // Tags and Tagging Tests
    //------------------------------------------------------------------

    /**
     * Data provider for tag operations
     */
    public static function tagOperationsProvider(): array
    {
        return [
          'tag multiple services' => [
             'services' => ['service1', 'service2'],
             'expectedCount' => 2,
          ],
          'tag with duplicate services' => [
             'services' => ['service1', 'service2', 'service1'],
             'expectedCount' => 2, // Duplicates are removed
          ],
        ];
    }

    /**
     * Tests tagging multiple services.
     *
     * @dataProvider tagOperationsProvider
     * @covers \WPTechnix\DI\Container::tag
     */
    public function testTagMultipleServices(array $services, int $expectedCount): void
    {
        // Register services first to avoid duplicate binding issues
        foreach ($services as $service) {
            if (!$this->container->hasBinding($service)) {
                $this->container->bind($service, fn() => new stdClass());
            }
        }

        $this->container->tag('test-tag', $services);

        $resolved = $this->container->resolveTagged('test-tag');
        $this->assertCount($expectedCount, $resolved);
    }

    /**
     * Tests tag with merge true.
     *
     * @covers \WPTechnix\DI\Container::tag
     */
    public function testTagWithMergeTrue(): void
    {
        $this->container->bind('service1', fn() => new stdClass());
        $this->container->bind('service2', fn() => new stdClass());
        $this->container->bind('service3', fn() => new stdClass());

        // Tag first service
        $this->container->tag('my-tag', ['service1']);

        // Tag more services with merge=true
        $this->container->tag('my-tag', ['service2', 'service3'], true);

        $services = $this->container->resolveTagged('my-tag');
        $this->assertCount(3, $services);
    }

    /**
     * Tests tag with merge false.
     *
     * @covers \WPTechnix\DI\Container::tag
     */
    public function testTagWithMergeFalse(): void
    {
        $this->container->bind('service1', fn() => new stdClass());
        $this->container->bind('service2', fn() => new stdClass());
        $this->container->bind('service3', fn() => new stdClass());

        // Tag first service
        $this->container->tag('my-tag', ['service1']);

        // Tag more services with merge=false (should replace)
        $this->container->tag('my-tag', ['service2', 'service3'], false);

        $services = $this->container->resolveTagged('my-tag');
        $this->assertCount(2, $services); // Only service2 and service3
    }

    /**
     * Tests resolving non-existent tag returns empty array.
     *
     * @covers \WPTechnix\DI\Container::resolveTagged
     */
    public function testResolveTaggedNonExistentTagReturnsEmptyArray(): void
    {
        $services = $this->container->resolveTagged('non-existent-tag');
        $this->assertEmpty($services);
    }

    /**
     * Tests resolving tagged with one error service.
     *
     * @covers \WPTechnix\DI\Container::resolveTagged
     * @covers \WPTechnix\DI\Exceptions\ResolutionException
     */
    public function testResolveTaggedWithOneErrorService(): void
    {
        $this->container->bind('good-service', function () {
            return new stdClass();
        });

        $this->container->bind('bad-service', function () {
            throw new RuntimeException('Service failure');
        });

        $this->container->tag('mixed-tag', ['good-service', 'bad-service']);

        $this->expectException(ResolutionException::class);
        $this->container->resolveTagged('mixed-tag');
    }

    /**
     * Tests tag with empty tag name fails.
     *
     * @covers \WPTechnix\DI\Container::tag
     * @covers \WPTechnix\DI\Exceptions\ContainerException
     */
    public function testTagWithEmptyTagNameFails(): void
    {
        $this->container->bind('service', function () {
            return new stdClass();
        });

        $this->expectException(ContainerException::class);
        $this->container->tag('', ['service']);
    }

    /**
     * Tests tag with non-existent service fails.
     *
     * @covers \WPTechnix\DI\Container::tag
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testTagWithNonExistentServiceFails(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->tag('my-tag', ['non-existent-service']);
    }

    /**
     * Tests tag with empty service array does nothing.
     *
     * @covers \WPTechnix\DI\Container::tag
     */
    public function testTagWithEmptyServiceArrayDoesNothing(): void
    {
        $this->container->tag('my-tag', []);

        $services = $this->container->resolveTagged('my-tag');
        $this->assertEmpty($services);
    }

    /**
     * Tests untagging a single service.
     *
     * @covers \WPTechnix\DI\Container::untag
     */
    public function testUntagSingleService(): void
    {
        $this->container->bind('service1', function () {
            return new stdClass();
        });

        $this->container->bind('service2', function () {
            return new stdClass();
        });

        $this->container->tag('my-tag', ['service1', 'service2']);

        // Untag one service
        $this->container->untag('my-tag', 'service1');

        $services = $this->container->resolveTagged('my-tag');
        $this->assertCount(1, $services);
    }

    /**
     * Tests untagging multiple services.
     *
     * @covers \WPTechnix\DI\Container::untag
     */
    public function testUntagMultipleServices(): void
    {
        $this->container->bind('service1', function () {
            return new stdClass();
        });

        $this->container->bind('service2', function () {
            return new stdClass();
        });

        $this->container->bind('service3', function () {
            return new stdClass();
        });

        $this->container->tag('my-tag', ['service1', 'service2', 'service3']);

        // Untag multiple services
        $this->container->untag('my-tag', ['service1', 'service3']);

        $services = $this->container->resolveTagged('my-tag');
        $this->assertCount(1, $services);
    }

    /**
     * Tests untagging non-existent service does not throw.
     *
     * @covers \WPTechnix\DI\Container::untag
     */
    public function testUntagNonExistentServiceDoesNotThrow(): void
    {
        $this->container->bind('service', function () {
            return new stdClass();
        });

        $this->container->tag('my-tag', ['service']);

        // Should not throw
        $this->container->untag('my-tag', 'non-existent-service');

        $services = $this->container->resolveTagged('my-tag');
        $this->assertCount(1, $services);

        $this->container->untag('non-existent-tag', 'test');
    }

    //------------------------------------------------------------------
    // Extension Tests
    //------------------------------------------------------------------

    /**
     * Tests extending a service.
     *
     * @covers \WPTechnix\DI\Container::extend
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testExtendService(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        $this->container->extend(TestInterface::class, function ($service) {
            return new class ($service) implements TestInterface {
                private TestInterface $inner;

                public function __construct(TestInterface $inner)
                {
                    $this->inner = $inner;
                }

                public function getName(): string
                {
                    return 'Extended: ' . $this->inner->getName();
                }
            };
        });

        $instance = $this->container->get(TestInterface::class);
        $this->assertEquals('Extended: Simple', $instance->getName());
    }

    /**
     * Tests extending a service multiple times.
     *
     * @covers \WPTechnix\DI\Container::extend
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testExtendMultipleTimes(): void
    {
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        $this->container->extend(TestInterface::class, function ($service) {
            return new class ($service) implements TestInterface {
                private TestInterface $inner;

                public function __construct(TestInterface $inner)
                {
                    $this->inner = $inner;
                }

                public function getName(): string
                {
                    return 'First: ' . $this->inner->getName();
                }
            };
        });

        $this->container->extend(TestInterface::class, function ($service) {
            return new class ($service) implements TestInterface {
                private TestInterface $inner;

                public function __construct(TestInterface $inner)
                {
                    $this->inner = $inner;
                }

                public function getName(): string
                {
                    return 'Second: ' . $this->inner->getName();
                }
            };
        });

        $instance = $this->container->get(TestInterface::class);
        $this->assertEquals('Second: First: Simple', $instance->getName());
    }

    /**
     * Tests extending a non-existent service fails.
     *
     * @covers \WPTechnix\DI\Container::extend
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testExtendNonExistentServiceFails(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->extend('non-existent-service', function ($service) {
            return $service;
        });
    }

    /**
     * Tests extending a service with a throwing extension.
     *
     * @covers \WPTechnix\DI\Container::extend
     * @covers \WPTechnix\DI\Container::resolve
     */
    public function testExtendWithThrowingExtension(): void
    {
        $this->container->bind('service', function () {
            return new stdClass();
        });

        $this->container->extend('service', function ($service) {
            throw new RuntimeException('Extension throws');
        });

        try {
            $this->container->get('service');
            $this->fail('Expected exception was not thrown');
        } catch (ResolutionException $e) {
            // This might wrap the RuntimeException, which is fine
            $this->assertTrue(true);
        } catch (RuntimeException $e) {
            // Or it might be the raw RuntimeException, which is also fine
            $this->assertTrue(true);
        }
    }

    //------------------------------------------------------------------
    // Unbind Tests
    //------------------------------------------------------------------

    /**
     * Tests unbinding a service.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::unbind
     * @covers \WPTechnix\DI\Container::hasBinding
     */
    public function testUnbindService(): void
    {
        $this->container->bind('service', function () {
            return new stdClass();
        });

        $this->assertTrue($this->container->hasBinding('service'));

        $this->container->unbind('service');

        $this->assertFalse($this->container->hasBinding('service'));
    }

    /**
     * Tests unbinding a service also removes extensions.
     *
     * @covers \WPTechnix\DI\Container::bind
     * @covers \WPTechnix\DI\Container::extend
     * @covers \WPTechnix\DI\Container::unbind
     */
    public function testUnbindServiceAlsoRemovesExtensions(): void
    {
        $this->container->bind('service', function () {
            return new stdClass();
        });

        $this->container->extend('service', function ($service) {
            $service->extended = true;
            return $service;
        });

        $this->container->unbind('service');

        $this->assertFalse($this->container->hasBinding('service'));

        // Rebind to verify extension is gone
        $this->container->bind('service', function () {
            return new stdClass();
        });

        $instance = $this->container->get('service');
        $this->assertFalse(isset($instance->extended));
    }

    /**
     * Tests unbinding a non-existent service fails.
     *
     * @covers \WPTechnix\DI\Container::unbind
     * @covers \WPTechnix\DI\Exceptions\ServiceNotFoundException
     */
    public function testUnbindNonExistentServiceFails(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->unbind('non-existent-service');
    }

    //------------------------------------------------------------------
    // Provider Tests
    //------------------------------------------------------------------

    /**
     * Tests provider registration.
     *
     * @covers \WPTechnix\DI\Container::provider
     */
    public function testProviderRegistration(): void
    {
        $this->container->provider(ServiceProvider::class);

        $this->assertTrue($this->container->hasBinding(TestInterface::class));
        $this->assertTrue($this->container->hasBinding(AnotherInterface::class));

        $testInstance = $this->container->get(TestInterface::class);
        $anotherInstance = $this->container->get(AnotherInterface::class);

        $this->assertInstanceOf(SimpleImplementation::class, $testInstance);
        $this->assertInstanceOf(ValueImplementation::class, $anotherInstance);
    }

    /**
     * Tests provider registration with class name.
     *
     * @covers \WPTechnix\DI\Container::provider
     */
    public function testProviderRegistrationWithClassName(): void
    {
        $this->container->provider(ServiceProvider::class);

        $this->assertTrue($this->container->hasBinding(TestInterface::class));
        $this->assertTrue($this->container->hasBinding(AnotherInterface::class));
    }

    /**
     * Tests provider that throws.
     *
     * @covers \WPTechnix\DI\Container::provider
     * @covers \WPTechnix\DI\Exceptions\ContainerException
     */
    public function testProviderThatThrows(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->provider(ThrowingServiceProvider::class);
    }

    //------------------------------------------------------------------
    // Exception Context Tests
    //------------------------------------------------------------------

    /**
     * Tests exception context not empty.
     *
     * @covers \WPTechnix\DI\Exceptions\ContainerException
     */
    public function testExceptionContextNotEmpty(): void
    {
        try {
            $this->container->get('NonExistentClass');
            $this->fail('Expected exception was not thrown');
        } catch (ServiceNotFoundException $e) {
            $this->assertIsArray($e->getContext());
            $this->assertNotEmpty($e->getContext());
            $this->assertIsArray($e->getDependencyChain());
            $this->assertNotEmpty($e->getDependencyChain());
        }
    }

    /**
     * Tests exception context contains service ID.
     *
     * @covers \WPTechnix\DI\Exceptions\ContainerException
     */
    public function testExceptionContextContainsServiceId(): void
    {
        try {
            $this->container->get('NonExistentClass');
            $this->fail('Expected exception was not thrown');
        } catch (ServiceNotFoundException $e) {
            $this->assertEquals('NonExistentClass', $e->getServiceId());
        }
    }

    /**
     * Tests exception debug info is formatted.
     *
     * @covers \WPTechnix\DI\Exceptions\ContainerException::getDebugInfo
     */
    public function testExceptionDebugInfoIsFormatted(): void
    {
        try {
            $this->container->get('NonExistentClass');
            $this->fail('Expected exception was not thrown');
        } catch (ServiceNotFoundException $e) {
            $debugInfo = $e->getDebugInfo();
            $this->assertStringContainsString('Service: NonExistentClass', $debugInfo);
            $this->assertStringContainsString('Context:', $debugInfo);
        }
    }
}
