# Contributing to WPTechnix DI Container

Thank you for considering contributing to the WPTechnix DI Container! This document outlines our coding standards, testing requirements, and contribution workflow.

## Code of Conduct

All participants in this project are expected to treat others with respect and follow the guidelines of open source collaboration.

## Coding Standards

We maintain strict coding standards to ensure code quality and consistency:

### PHP Code Requirements

- **PSR-12 Compliance**: All code MUST follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- **PHP 8.1+ Features**: Utilize PHP 8.1+ features appropriately (attributes, union types, etc.)
- **Type Safety**:
    - All method parameters MUST include type declarations
    - All methods MUST include return type declarations
    - Use union types instead of docblock `@param` annotations where appropriate
- **PHPStan Level 8**: Code MUST pass PHPStan analysis at level 8 without exceptions
- **Strict Types**: All PHP files MUST include `declare(strict_types=1)`
- **DocBlocks**:
    - All classes MUST have descriptive DocBlocks
    - All public and protected methods MUST have complete DocBlocks with `@param`, `@return`, and `@throws` annotations
    - DocBlocks SHOULD include descriptions that explain "why" not just "what"
- **SOLID Principles**:
    - Single Responsibility: Classes should have only one reason to change
    - Open/Closed: Open for extension, closed for modification
    - Liskov Substitution: Subtypes must be substitutable for their base types
    - Interface Segregation: Clients shouldn't depend on interfaces they don't use
    - Dependency Inversion: Depend on abstractions, not concretions

### Naming Conventions

- **Classes**: PascalCase, descriptive nouns (e.g., `ContainerInterface`, `ServiceProvider`)
- **Interfaces**: PascalCase, usually ending with "Interface" (e.g., `ProviderInterface`)
- **Methods**: camelCase, typically starting with verbs (e.g., `resolveClass`, `addContextualBinding`)
- **Variables**: camelCase, clear and descriptive (e.g., `$dependencyChain` not `$dc`)
- **Constants**: UPPER_SNAKE_CASE

## Testing Requirements

All contributions MUST include appropriate tests:

- **Unit Tests**: Write PHPUnit tests for all new features and bug fixes
- **Test Coverage**: Aim for 100% code coverage for new code
- **Edge Cases**: Test boundary conditions and exception paths
- **Isolated Testing**: Tests should not depend on external services
- **Naming**: Test methods should be named descriptively like `test_resolve_throws_exception_when_circular_dependency_detected`

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/path/to/test/file.php

# Generate coverage report
composer tests
```

## Development Process

### Getting Started

```bash
# Clone the repository
git clone https://github.com/wptechnix/di-container.git
cd di-container

# Install dependencies
composer install

# Run tests
composer test

# Run PHPStan analysis
composer phpstan

# Check coding standards
composer phpcs
```

### Branching Strategy

We use a simplified approach to git branching:

- `main` - Contains the stable, released code
- `develop` - Integration branch for features and non-urgent bug fixes
- `feature/xxx` - Feature branches branched from develop
- `fix/xxx` - Bug fix branches branched from develop

For critical production bugs, fixes can be branched directly from `main`.

### Documentation

Maintaining high-quality documentation is crucial:

- Update README.md with new features or changes in behavior
- Add/update PHPDoc blocks for all public API methods
- Update CHANGELOG.md following the Keep a Changelog format
- Document exceptions and edge cases

## Pull Request Process

1. Ensure your code adheres to our coding standards
2. Add/update tests for your changes
3. Update relevant documentation
4. Create a pull request to the `develop` branch (unless it's a critical fix)
5. Include a clear description of the changes and their purpose
6. Reference any related issues

## Code Review

Pull requests are reviewed according to these criteria:

- Code adheres to our coding standards
- Tests are comprehensive and pass
- Documentation is updated
- PHPStan passes at level 8
- CHANGELOG.md is updated appropriately

## Questions?

If you have questions about contributing, please open an issue and we'll be happy to help!
