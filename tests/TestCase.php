<?php
/**
 * Base test case for all tests.
 *
 * @package WPTechnix\DI\Tests
 * @author WPTechnix <developer@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\Tests\DI;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use ReflectionException;
use WPTechnix\DI\Container;

/**
 * Base test case for all tests.
 *
 * @package WPTechnix\DI\Tests
 * @author WPTechnix <developer@wptechnix.com>
 */
class TestCase extends PHPUnitTestCase {
	
	/**
	 * @var Container
	 */
	protected Container $container;
	
	/**
	 * Set up a fresh container for each test.
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->container = new Container();
	}
	
	/**
	 * Helper method to set up common interface bindings.
	 */
	protected function setupInterfaceBindings(): void
	{
		$this->container->bind(TestInterface::class, SimpleImplementation::class);
		$this->container->bind(AnotherInterface::class, ValueImplementation::class);
		$this->container->bind(AbstractClass::class, SimpleImplementation::class);
	}
	
	/**
	 * Helper method to access private properties for testing internal state.
	 * @throws ReflectionException
	 */
	protected function getPrivateProperty(object $object, string $property)
	{
		$reflection = new ReflectionClass($object);
		$prop = $reflection->getProperty($property);
		$prop->setAccessible(true);
		return $prop->getValue($object);
	}
	
	/**
	 * Helper method to set private properties for testing.
	 * @throws ReflectionException
	 */
	protected function setPrivateProperty(object $object, string $property, $value): void
	{
		$reflection = new ReflectionClass($object);
		$prop = $reflection->getProperty($property);
		$prop->setAccessible(true);
		$prop->setValue($object, $value);
	}
}