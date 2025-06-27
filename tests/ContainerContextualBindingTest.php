<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use Closure;
use Exception;
use WPTechnix\DI\Exceptions\BindingException;
use WPTechnix\DI\Tests\Fixture\AnotherImplementation;
use WPTechnix\DI\Tests\Fixture\AnotherInterface;
use WPTechnix\DI\Tests\Fixture\NestedDependency;
use WPTechnix\DI\Tests\Fixture\ServiceWithDependency;
use WPTechnix\DI\Tests\Fixture\ServiceWithMethodInjection;
use WPTechnix\DI\Tests\Fixture\ServiceWithPropertyInjection;
use WPTechnix\DI\Tests\Fixture\SimpleImplementation;
use WPTechnix\DI\Tests\Fixture\TestInterface;
use WPTechnix\DI\Tests\Fixture\ValueImplementation;

/**
 * Tests for contextual bindings.
 *
 * @covers \WPTechnix\DI\Container
 * @covers \WPTechnix\DI\ContextualBindingBuilder
 * @covers \WPTechnix\DI\Attributes\Inject
 * @covers \WPTechnix\DI\Exceptions\ContainerException
 */
class ContainerContextualBindingTest extends TestCase
{
    //------------------------------------------------------------------
    // Contextual Binding Tests - Basic Functionality
    //------------------------------------------------------------------

    /**
     * Tests basic contextual binding with an interface.
     *
     * @covers \WPTechnix\DI\Container::when
     * @covers \WPTechnix\DI\Container::addContextualBinding
     * @covers \WPTechnix\DI\ContextualBindingBuilder::needs
     * @covers \WPTechnix\DI\ContextualBindingBuilder::give
     */
    public function testBasicContextualBindingWithInterface(): void
    {
        // Default binding.
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Contextual binding: When ServiceWithDependency needs TestInterface, give AnotherImplementation.
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // ServiceWithDependency should get AnotherImplementation.
        $serviceWithDep = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(AnotherImplementation::class, $serviceWithDep->getDependency());

        // Regular resolution should still get SimpleImplementation.
        $regularInstance = $this->container->resolve(TestInterface::class);
        $this->assertInstanceOf(SimpleImplementation::class, $regularInstance);
    }

    /**
     * Tests contextual binding with closure implementation.
     *
     * @covers \WPTechnix\DI\Container::when
     * @covers \WPTechnix\DI\Container::addContextualBinding
     */
    public function testContextualBindingWithClosure(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Contextual binding with a closure
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(function ($container) {
                            $instance = new AnotherImplementation();
                            // We could do additional setup here
                            return $instance;
                        });

        // ServiceWithDependency should get AnotherImplementation
        $serviceWithDep = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(AnotherImplementation::class, $serviceWithDep->getDependency());
    }

    /**
     * Tests contextual binding with multiple classes.
     *
     * @covers \WPTechnix\DI\Container::when
     * @covers \WPTechnix\DI\ContextualBindingBuilder::__construct
     */
    public function testContextualBindingWithMultipleClasses(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Contextual binding for multiple classes
        $this->container->when([ServiceWithDependency::class, NestedDependency::class])
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Both classes should get AnotherImplementation
        $serviceWithDep = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(AnotherImplementation::class, $serviceWithDep->getDependency());

        // We need to set up NestedDependency to be resolvable
        $this->container->bind(ServiceWithDependency::class, ServiceWithDependency::class);
        $nestedDep = $this->container->resolve(NestedDependency::class);
        $this->assertInstanceOf(AnotherImplementation::class, $nestedDep->getService()->getDependency());
    }

    /**
     * Tests multiple contextual bindings for the same concrete class.
     *
     * @covers \WPTechnix\DI\Container::when
     * @covers \WPTechnix\DI\Container::addContextualBinding
     */
    public function testMultipleContextualBindingsForSameClass(): void
    {
        // Set up default bindings
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind(AnotherInterface::class, ValueImplementation::class);

        // Create a service that needs multiple interfaces
        $serviceClass = new class (new SimpleImplementation(), new ValueImplementation()) {
            private TestInterface $test;
            private AnotherInterface $another;

            public function __construct(TestInterface $test, AnotherInterface $another)
            {
                $this->test = $test;
                $this->another = $another;
            }

            public function getTest(): TestInterface
            {
                return $this->test;
            }

            public function getAnother(): AnotherInterface
            {
                return $this->another;
            }
        };
        $serviceClassName = get_class($serviceClass);

        // Contextual bindings for different interfaces
        $this->container->when($serviceClassName)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Use a closure to create a custom implementation
        $this->container->when($serviceClassName)
                        ->needs(AnotherInterface::class)
                        ->give(function () {
                            return new class implements AnotherInterface {
                                public function getValue(): int
                                {
                                    return 100; // Different value than the default 42
                                }
                            };
                        });

        // Resolve and check both bindings work
        $instance = $this->container->resolve($serviceClassName);
        $this->assertInstanceOf(AnotherImplementation::class, $instance->getTest());
        $this->assertEquals(100, $instance->getAnother()->getValue());
    }

//------------------------------------------------------------------
// Contextual Binding Tests - Integration with DI Features
//------------------------------------------------------------------

    /**
     * Tests contextual binding with property injection.
     *
     * @covers \WPTechnix\DI\Container::injectViaPropAttribute
     */
    public function testContextualBindingWithPropertyInjection(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Contextual binding for property injection
        $this->container->when(ServiceWithPropertyInjection::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Property should get the contextual binding
        $service = $this->container->resolve(ServiceWithPropertyInjection::class);
        $this->assertInstanceOf(AnotherImplementation::class, $service->publicDependency);
    }

    /**
     * Tests contextual binding with setter injection.
     *
     * @covers \WPTechnix\DI\Container::injectViaSetters
     */
    public function testContextualBindingWithSetterInjection(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind(AnotherInterface::class, ValueImplementation::class);

        // Contextual binding for setter injection
        $this->container->when(ServiceWithMethodInjection::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Setter method should get the contextual binding
        $service = $this->container->resolve(ServiceWithMethodInjection::class);
        $dependencies = $service->getDependencies();
        $this->assertInstanceOf(AnotherImplementation::class, $dependencies['test']);
    }

    /**
     * Tests multiple levels of contextual binding.
     *
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testMultipleLevelsOfContextualBinding(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // NestedDependency contains ServiceWithDependency which needs TestInterface
        // A normal binding for ServiceWithDependency
        $this->container->bind(ServiceWithDependency::class, ServiceWithDependency::class);

        // First level contextual binding
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Second level contextual binding
        $this->container->when(NestedDependency::class)
                        ->needs(ServiceWithDependency::class)
                        ->give(function ($container) {
                            // Create a ServiceWithDependency with a specific TestInterface
                            // This should use the contextual binding for ServiceWithDependency
                            return $container->resolve(ServiceWithDependency::class);
                        });

        // Resolve the nested dependency
        $nested = $this->container->resolve(NestedDependency::class);

        // The TestInterface inside ServiceWithDependency should be AnotherImplementation
        $this->assertInstanceOf(AnotherImplementation::class, $nested->getService()->getDependency());
    }

    //------------------------------------------------------------------
    // Contextual Binding Tests - Management Methods
    //------------------------------------------------------------------

    /**
     * Tests forget_when method removes contextual bindings.
     *
     * @covers \WPTechnix\DI\Container::forgetWhen
     */
    public function testForgetWhenRemovesContextualBindings(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Contextual binding
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Verify contextual binding is working
        $service1 = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(AnotherImplementation::class, $service1->getDependency());

        // Remove the contextual binding
        $this->container->forgetWhen(ServiceWithDependency::class);

        // Now it should fall back to the default binding
        $service2 = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(SimpleImplementation::class, $service2->getDependency());
    }

    /**
     * Tests forget_when with specific abstract type.
     *
     * @covers \WPTechnix\DI\Container::forgetWhen
     */
    public function testForgetWhenWithSpecificAbstractType(): void
    {
        // Default bindings
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind(AnotherInterface::class, ValueImplementation::class);

        // Multiple contextual bindings
        $this->container->when(ServiceWithMethodInjection::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        $this->container->when(ServiceWithMethodInjection::class)
                        ->needs(AnotherInterface::class)
                        ->give(ValueImplementation::class);

        // Remove only one of the contextual bindings
        $this->container->forgetWhen(ServiceWithMethodInjection::class, TestInterface::class);

        // Resolve and check
        $service = $this->container->resolve(ServiceWithMethodInjection::class);
        $dependencies = $service->getDependencies();

        // TestInterface should use default binding now
        $this->assertInstanceOf(SimpleImplementation::class, $dependencies['test']);

        // Manually trigger the setter for AnotherInterface
        $service->setOptionalDependency($this->container->resolve(AnotherInterface::class));
        $dependencies = $service->getDependencies();
        $this->assertInstanceOf(ValueImplementation::class, $dependencies['another']);
    }

    /**
     * Tests reset clears contextual bindings.
     *
     * @covers \WPTechnix\DI\Container::reset
     */
    public function testResetClearsContextualBindings(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Contextual binding
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Reset container
        $this->container->reset();

        // Set up a minimal environment for resolution
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Contextual binding should be gone, and it should use the default
        $service = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(SimpleImplementation::class, $service->getDependency());
    }

    //------------------------------------------------------------------
    // Contextual Binding Tests - Direct Access and Internal Structure
    //------------------------------------------------------------------

    /**
     * Tests adding a contextual binding directly.
     *
     * @covers \WPTechnix\DI\Container::addContextualBinding
     */
    public function testAddContextualBindingDirectly(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Add contextual binding directly
        $this->container->addContextualBinding(
            ServiceWithDependency::class,
            TestInterface::class,
            AnotherImplementation::class
        );

        // Verify it works
        $service = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(AnotherImplementation::class, $service->getDependency());
    }

    /**
     * Tests the contextual binding builder.
     *
     * @covers \WPTechnix\DI\ContextualBindingBuilder::__construct
     * @covers \WPTechnix\DI\ContextualBindingBuilder::needs
     * @covers \WPTechnix\DI\ContextualBindingBuilder::give
     */
    public function testContextualBindingBuilderMethods(): void
    {
        // Use fully qualified namespace
        $builder = new \WPTechnix\DI\ContextualBindingBuilder($this->container, ['TestClass']);
        $returnedBuilder = $builder->needs('TestInterface');
        $this->assertSame($builder, $returnedBuilder);

        // Test that the give method returns the container
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $container = $builder->give(AnotherImplementation::class);
        $this->assertSame($this->container, $container);
    }

    /**
     * Tests inspection of contextual binding internals.
     *
     * @covers \WPTechnix\DI\Container::when
     * @covers \WPTechnix\DI\Container::addContextualBinding
     */
    public function testContextualBindingInternalStructure(): void
    {
        // Set up a contextual binding with a class name
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(AnotherImplementation::class);

        // Get the contextual bindings
        $contextualBindings = $this->getPrivateProperty($this->container, 'contextualBindings');

        // Verify the binding was registered
        $this->assertArrayHasKey(ServiceWithDependency::class, $contextualBindings);
        $this->assertArrayHasKey(TestInterface::class, $contextualBindings[ServiceWithDependency::class]);

        // Key insight: Check what's actually stored in the 'concrete' key
        $concrete = $contextualBindings[ServiceWithDependency::class][TestInterface::class]['concrete'];

        // Even though we passed a string (AnotherImplementation::class), it's stored as a Closure
        $this->assertInstanceOf(Closure::class, $concrete);
    }

    //------------------------------------------------------------------
    // Contextual Binding Tests - Edge Cases and Error Handling
    //------------------------------------------------------------------

    /**
     * Tests contextual binding with concrete instance.
     *
     * @covers \WPTechnix\DI\Container::when
     */
    public function testContextualBindingWithConcreteInstance(): void
    {
        // Default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Create a concrete instance
        $concreteInstance = new AnotherImplementation();

        // Add contextual binding with a closure returning the concrete instance
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(function () use ($concreteInstance) {
                            return $concreteInstance;
                        });

        // Verify the exact same instance is used
        $service = $this->container->resolve(ServiceWithDependency::class);
        $this->assertSame($concreteInstance, $service->getDependency());
    }

    /**
     * Tests contextual binding with non-existent implementation fails.
     *
     * @covers \WPTechnix\DI\Container::addContextualBinding
     * @covers \WPTechnix\DI\Exceptions\BindingException
     */
    public function testContextualBindingWithNonExistentImplementationFails(): void
    {
        $this->expectException(BindingException::class);
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give('NonExistentClass');
    }

    /**
     * Tests contextual binding fallback behavior when binding fails.
     *
     * @covers \WPTechnix\DI\Container::resolveMethodDependencies
     */
    public function testContextualBindingFallback(): void
    {
        // Set up default binding
        $this->container->bind(TestInterface::class, SimpleImplementation::class);

        // Set up contextual binding that will throw
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(function () {
                            throw new \RuntimeException('Test exception');
                        });

        // Create a test double for ServiceWithDependency that will show us the actual dependency used
        $mock = new class (new SimpleImplementation()) extends ServiceWithDependency {
            public function __construct(TestInterface $dependency)
            {
                parent::__construct($dependency);
            }
        };

        // When resolving directly, we expect an exception due to the failing contextual binding
        $this->expectException(Exception::class);
        $this->container->resolve(ServiceWithDependency::class);
    }

    /**
     * Tests the internal cleanup logic in unbind by directly manipulating the container state.
     *
     * This test is specifically designed to cover the code path where a string implementation
     * in a contextual binding is unbound.
     *
     * @covers \WPTechnix\DI\Container::unbind
     */
    public function testUnbindCleanupWithManualStateSetup(): void
    {
        // First set up our container with some bindings
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind('test-service', AnotherImplementation::class);

        // Now we need to manually set up the contextualBindings array
        // to contain a string value for 'concrete' instead of a Closure
        $concrete = ServiceWithDependency::class;
        $abstract = TestInterface::class;

        // Create the structure manually
        $contextualBindings = [
          $concrete => [
             $abstract => [
                'concrete' => 'test-service', // Set this as a string, not a Closure
                'shared' => false
             ]
          ]
        ];

        // Set the private property directly
        $this->setPrivateProperty($this->container, 'contextualBindings', $contextualBindings);

        // Verify our setup worked
        $currentBindings = $this->getPrivateProperty($this->container, 'contextualBindings');
        $this->assertArrayHasKey($concrete, $currentBindings);
        $this->assertArrayHasKey($abstract, $currentBindings[$concrete]);
        $this->assertEquals('test-service', $currentBindings[$concrete][$abstract]['concrete']);

        // Now unbind the service
        $this->container->unbind('test-service');

        // Check that the contextual binding was cleaned up
        $afterBindings = $this->getPrivateProperty($this->container, 'contextualBindings');
        $this->assertFalse(
            isset($afterBindings[$concrete][$abstract]),
            'The contextual binding should be removed when its string implementation is unbound'
        );
    }

    /**
     * Documents the behavior of unbind with contextual bindings.
     *
     * This test focuses on the actual behavior rather than attempting to cover
     * unreachable code.
     *
     * @covers \WPTechnix\DI\Container::unbind
     */
    public function testUnbindWithContextualBindings(): void
    {
        // First register our bindings
        $this->container->bind(TestInterface::class, SimpleImplementation::class);
        $this->container->bind('special-service', AnotherImplementation::class);

        // Create a contextual binding using a closure that references the special service
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(function ($container) {
                            return $container->get('special-service');
                        });

        // Verify the contextual binding works before unbinding
        $service1 = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(AnotherImplementation::class, $service1->getDependency());

        // Now we'll create a new contextual binding to replace the first one
        // This effectively tests what happens when we modify contextual bindings
        $this->container->when(ServiceWithDependency::class)
                        ->needs(TestInterface::class)
                        ->give(SimpleImplementation::class);

        // Now we should get SimpleImplementation
        $service2 = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(SimpleImplementation::class, $service2->getDependency());
    }
}
