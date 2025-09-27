# Contributing to Parish Management System

Thank you for your interest in contributing to the Parish Management System! This document provides guidelines and information for contributors.

## 🤝 Code of Conduct

By participating in this project, you agree to abide by our Code of Conduct:

- **Be Respectful**: Treat all community members with respect and courtesy
- **Be Inclusive**: Welcome newcomers and help them get started
- **Be Collaborative**: Work together towards common goals
- **Be Professional**: Maintain professional communication and behavior
- **Respect Privacy**: Handle sensitive religious and personal data appropriately

## 🛠️ Development Setup

### Prerequisites
- PHP 8.2 or higher
- Node.js 18.0 or higher
- Composer 2.0+
- Git

### Local Development Environment
```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/parish-management-system.git
cd parish-management-system

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Set up database
php artisan migrate
php artisan db:seed

# Start development servers
npm run dev        # Frontend assets (separate terminal)
php artisan serve  # Backend server
```

## 📝 Contributing Guidelines

### 1. Finding Issues to Work On

- Check [GitHub Issues](https://github.com/JosephNjoroge8/parish-management-system/issues)
- Look for issues labeled `good first issue` for beginners
- Issues labeled `help wanted` are ready for community contributions
- Feel free to propose new features by opening an issue first

### 2. Making Changes

#### Fork and Branch Strategy
```bash
# Fork the repository on GitHub, then:
git clone https://github.com/YOUR_USERNAME/parish-management-system.git
cd parish-management-system

# Create a feature branch
git checkout -b feature/your-feature-name
# or
git checkout -b fix/issue-description
```

#### Branch Naming Conventions
- `feature/description` - for new features
- `fix/issue-description` - for bug fixes
- `docs/description` - for documentation changes
- `refactor/description` - for code refactoring
- `test/description` - for adding tests

### 3. Coding Standards

#### PHP Standards (Backend)
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add PHPDoc comments for classes and methods
- Write unit tests for new functionality

```php
<?php

namespace App\Http\Controllers;

/**
 * Handles member management operations
 */
class MemberController extends Controller
{
    /**
     * Store a new member in the database
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Implementation
    }
}
```

#### JavaScript/TypeScript Standards (Frontend)
- Use ESLint and Prettier configurations
- Follow React best practices and hooks guidelines
- Use TypeScript for type safety
- Write descriptive component and function names

```typescript
interface MemberFormProps {
    member?: Member;
    onSave: (member: Member) => void;
}

const MemberForm: React.FC<MemberFormProps> = ({ member, onSave }) => {
    // Component implementation
};
```

#### Database Standards
- Use descriptive migration names: `2024_01_15_create_members_table.php`
- Include rollback functionality in migrations
- Use proper foreign key constraints
- Add indexes for frequently queried columns

### 4. Testing Requirements

#### Backend Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

#### Frontend Tests
```bash
# Run frontend tests
npm run test

# Run with watch mode
npm run test:watch
```

#### Test Guidelines
- Write tests for all new features
- Maintain or improve test coverage
- Test both happy path and error cases
- Use meaningful test descriptions

### 5. Documentation

- Update README.md if adding new features
- Add inline comments for complex logic
- Update API documentation for new endpoints
- Include migration notes for breaking changes

### 6. Commit Guidelines

#### Commit Message Format
```
<type>(<scope>): <subject>

<body>

<footer>
```

#### Types
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

#### Examples
```
feat(members): add bulk import functionality

- Implement CSV import for member data
- Add validation for imported data
- Include progress indicator for large imports

Closes #123
```

```
fix(auth): resolve login redirect issue

Fix redirect loop when accessing protected routes
after successful authentication.

Fixes #456
```

### 7. Pull Request Process

#### Before Submitting
- [ ] Ensure all tests pass
- [ ] Update documentation if needed
- [ ] Follow coding standards
- [ ] Test your changes thoroughly
- [ ] Rebase your branch on the latest main

#### Pull Request Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added tests for new functionality
- [ ] Manual testing completed

## Screenshots (if applicable)
Add screenshots for UI changes

## Checklist
- [ ] Code follows project standards
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No sensitive data exposed
```

#### Review Process
1. Automated tests must pass
2. At least one maintainer review required
3. Address feedback promptly
4. Keep PR focused and small when possible

## 🐛 Reporting Bugs

### Bug Report Template
```markdown
**Bug Description**
Clear description of the bug

**Steps to Reproduce**
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected Behavior**
What should happen

**Actual Behavior**
What actually happens

**Screenshots**
If applicable, add screenshots

**Environment**
- OS: [e.g. Windows 10]
- Browser: [e.g. Chrome 91]
- PHP Version: [e.g. 8.2]
- Laravel Version: [e.g. 11.0]

**Additional Context**
Any other context about the problem
```

## 💡 Feature Requests

### Feature Request Template
```markdown
**Feature Description**
Clear description of the feature

**Problem Statement**
What problem does this solve?

**Proposed Solution**
How should this be implemented?

**Alternatives Considered**
Other solutions you've considered

**Additional Context**
Screenshots, mockups, or examples
```

## 🚀 Release Process

### Version Numbers
We follow [Semantic Versioning](https://semver.org/):
- `MAJOR.MINOR.PATCH` (e.g., 2.1.3)
- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes (backward compatible)

### Release Schedule
- Major releases: Every 6-12 months
- Minor releases: Every 2-3 months
- Patch releases: As needed for critical fixes

## 📚 Resources

### Learning Resources
- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://reactjs.org/docs)
- [TypeScript Documentation](https://www.typescriptlang.org/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

### Development Tools
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)
- [React Developer Tools](https://react.dev/learn/react-developer-tools)
- [PHPStorm](https://www.jetbrains.com/phpstorm/) or [VS Code](https://code.visualstudio.com/)

## 🆘 Getting Help

### Community Support
- GitHub Issues: Technical problems and feature requests
- GitHub Discussions: General questions and community chat
- Email: maintainers@parish-system.com

### Response Times
- Critical bugs: 24-48 hours
- Feature requests: 1-2 weeks
- General questions: 3-5 days

## 🎉 Recognition

Contributors will be recognized in:
- GitHub contributors list
- Release notes for significant contributions
- Hall of Fame in documentation (for major contributors)

## 📋 Maintainer Guidelines

### For Project Maintainers

#### Review Checklist
- [ ] Code quality and standards compliance
- [ ] Test coverage adequate
- [ ] Documentation updated
- [ ] No sensitive data exposed
- [ ] Performance impact considered
- [ ] Security implications reviewed

#### Merging Guidelines
- Squash merge for feature branches
- Regular merge for hotfixes
- Always update CHANGELOG.md
- Tag releases appropriately

---

Thank you for contributing to the Parish Management System! Your efforts help parishes worldwide manage their communities more effectively. 🙏

For questions about contributing, please open an issue or contact the maintainers directly.