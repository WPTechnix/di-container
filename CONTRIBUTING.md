# Contributing to WPTechnix DI Container

Thank you for considering contributing to the WPTechnix DI Container! This document outlines our coding standards, testing requirements, and contribution workflow.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Quick Start](#quick-start)
- [Coding Standards](#coding-standards)
    - [PHP Code Requirements](#php-code-requirements)
    - [Naming Conventions](#naming-conventions)
- [Testing Requirements](#testing-requirements)
    - [Running Tests](#running-tests)
- [Development Process](#development-process)
    - [Getting Started](#getting-started)
    - [Branching Strategy](#branching-strategy)
    - [Commit Message Guidelines](#commit-message-guidelines)
    - [Documentation](#documentation)
- [Pull Request Process](#pull-request-process)
- [Code Review](#code-review)
- [Common Issues and Troubleshooting](#common-issues-and-troubleshooting)
- [Questions?](#questions)

## Code of Conduct

All participants in this project are expected to treat others with respect and follow the guidelines of open source collaboration.

## Quick Start

```bash
# Clone the repository
git clone https://github.com/wptechnix/di-container.git
cd di-container

# Install dependencies
composer install

# Run tests to make sure everything is working
composer test

# Create a new branch for your feature or bugfix
git checkout -b feature/your-feature-name

# Make your changes, write tests, then run checks
composer test
composer phpstan
composer phpcs

# Submit a pull request to the develop branch
```

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
    - Use proper type hints in DocBlocks, especially for arrays (`array<string, mixed>` instead of `array`)
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

# Generate coverage report (with HTML output)
composer tests   # This creates a code-coverage directory with HTML report
```

## Development Process

### Getting Started

1. **Fork the Repository**: Begin by forking the repository to your own GitHub account.

2. **Clone Your Fork**:
   ```bash
   git clone https://github.com/YOUR-USERNAME/di-container.git
   cd di-container
   ```

3. **Add Upstream Remote**:
   ```bash
   git remote add upstream https://github.com/wptechnix/di-container.git
   ```

4. **Install Dependencies**:
   ```bash
   composer install
   ```

5. **Set Up Development Environment**:
    - Ensure your PHP version is 8.1 or higher
    - Configure your IDE for PSR-12 compliance
    - Install recommended extensions for your editor (PHPStan, PHP CS Fixer, etc.)

### Branching Strategy

We use a simplified approach to git branching:

- `main` - Contains the stable, released code
- `develop` - Integration branch for features and non-urgent bug fixes
- `feature/xxx` - Feature branches branched from develop
- `fix/xxx` - Bug fix branches branched from develop

For critical production bugs, fixes can be branched directly from `main`.

### Commit Message Guidelines

We follow conventional commits for clear and structured commit messages:

- **Format**: `type(scope): description`
- **Types**:
    - `feat`: A new feature or enhancement to existing functionality
    - `fix`: A bug fix or error correction
    - `docs`: Documentation changes only (README, CONTRIBUTING, PHPDoc comments)
    - `refactor`: Code restructuring that doesn't change functionality (may include style changes)
    - `test`: Adding, modifying, or fixing tests
    - `chore`: Maintenance tasks, dependency updates, CI changes

Examples:
- `feat(container): add method to reset contextual bindings`
- `fix(autowiring): resolve issue with union types in PHP 8.1`
- `docs(readme): update installation instructions`
- `refactor(container): simplify dependency resolution logic`
- `test(contextual): add tests for edge cases in contextual binding`

### Documentation

- Update README.md with new features, changes in behavior, and usage examples
    - The README.md is our primary documentation, so be thorough and clear
    - Include code examples for any new functionality
    - Ensure the "Features" and "Basic Usage" sections stay current
- Add/update PHPDoc blocks for all public API methods
    - PHPDoc comments serve as inline documentation and should be comprehensive
    - Include `@throws` annotations for all possible exceptions
- Update CHANGELOG.md following the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format
- Document exceptions and edge cases in both README.md and PHPDoc comments

## Pull Request Process

1. **Create a Branch**:
   ```bash
   git checkout develop
   git pull upstream develop
   git checkout -b feature/your-feature-name
   ```

2. **Make Changes**:
    - Write your code following our coding standards
    - Add/update tests for your changes
    - Update relevant documentation

3. **Run Quality Checks**:
   ```bash
   composer test     # Run PHPUnit tests
   composer phpstan  # Run static analysis
   composer phpcs    # Check coding standards
   ```

4. **Commit Your Changes**:
   ```bash
   git add .
   git commit -m "feat(component): brief description of changes"
   ```

5. **Push to Your Fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Submit a Pull Request**:
    - Go to your fork on GitHub and click "New Pull Request"
    - Choose the `develop` branch as the target (unless it's a critical fix)
    - Fill in the PR template with details about your changes
    - Reference any related issues using GitHub keywords (e.g., "Fixes #123")

7. **Code Review**:
    - Be responsive to feedback and make requested changes
    - Push additional commits to the same branch as needed
    - The maintainers will merge your PR when it's ready

## Code Review

Pull requests are reviewed according to these criteria:

- [x] Code adheres to our coding standards
- [x] Tests are comprehensive and pass
- [x] Documentation is updated
- [x] PHPStan passes at level 8 with strict rules
- [x] CHANGELOG.md is updated appropriately
- [x] New features are properly documented with example usage

## Common Issues and Troubleshooting

### PHPStan Errors

If you encounter PHPStan errors:
- Ensure you're using proper type hints (including generics like `array<string, mixed>`)
- Check for nullable types that need special handling
- Use PHPStan annotations like `@phpstan-var` and `@phpstan-param` for complex types
- Review the error output in `phpstan.txt` after running `composer phpstan`

### Test Failures

If tests are failing:
- Run a specific test file to isolate the issue: `vendor/bin/phpunit tests/path/to/test/file.php`
- Check for environment differences (PHP version, extensions)
- Ensure dependencies are properly installed with `composer install`
- Look at the test coverage report to identify uncovered code sections

### Coding Standard Issues

If PHPCS reports issues:
- Run `composer phpcbf` to automatically fix some issues
- Review PSR-12 documentation for manual fixes
- Check `phpcs.txt` after running `composer phpcs` for detailed error messages
- Common issues include line length, spacing, and documentation formatting

## Questions?

If you have questions about contributing, please open an issue and we'll be happy to help!
