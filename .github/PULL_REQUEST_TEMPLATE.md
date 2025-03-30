### Pull Request Template Improvement

Here's an improved PULL_REQUEST_TEMPLATE.md with more specific references to your coding standards:

```markdown
## Description
Please include a summary of the change and which issue is fixed. Include relevant motivation and context.

Fixes # (issue)

## Type of change
Please delete options that are not relevant.

- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Refactoring (no functional changes)
- [ ] Performance improvement

## Coding Standards Checklist
- [ ] My code follows PSR-12 coding style standards
- [ ] All new classes have appropriate DocBlocks with @package and @author tags
- [ ] All new methods have complete DocBlocks with @param, @return, and @throws annotations
- [ ] Type declarations are used for all parameters and return values
- [ ] Code passes PHPStan analysis at level 8
- [ ] All files include `declare(strict_types=1)` at the top
- [ ] SOLID principles are followed (particularly single responsibility)
- [ ] Exceptions are properly hierarchical and include context data

## Testing Checklist
- [ ] I have added tests that cover the introduced changes
- [ ] New and existing unit tests pass locally
- [ ] Tests include edge cases and exception paths
- [ ] Test coverage remains at or above current levels

## Documentation Checklist
- [ ] I have updated relevant documentation in PHPDoc blocks
- [ ] I have updated the README.md (if applicable)
- [ ] I have updated the CHANGELOG.md following Keep a Changelog format

## Additional Information
Any additional information, screenshots, or context that would be helpful for reviewers.
