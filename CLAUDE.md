# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Testing
```bash
# Run all tests
vendor/bin/phpunit

# Run tests with bootstrap
vendor/bin/phpunit --bootstrap vendor/autoload.php tests

# Run specific test file
vendor/bin/phpunit tests/Models/UserTest.php
```

### Static Analysis
```bash
# Run PHPStan analysis
vendor/bin/phpstan analyse src tests
```

### Database Operations
```bash
# Run migrations to create database tables
php tools/migrations.php

# Seed database with test data (run migrations first)
php tools/seeders.php

# Generate encryption key for .env file
php tools/setkey.php
```

### Development Server
```bash
# Start PHP built-in server from public directory
cd public && php -S localhost:8000
```

## Architecture Overview

This is a custom PHP 8.3+ MVC framework built without third-party dependencies (except dev tools). Key architectural components:

### Dependency Injection Container
- **Location**: `src/Core/Container.php`
- **Features**: Service registration, singleton support, autowiring
- **Usage**: All services registered in `bootstrap.php` via definition classes

### Definition Classes
- **DatabaseDefinitions**: PDO and database service registration
- **ModelsDefinitions**: Model class factory definitions  
- **RoutingDefinitions**: Route configuration and controller mapping
- **Location**: `src/Definitions/`

### Routing System
- **Router**: `src/Core/Router.php` - Simple route matching and controller dispatch
- **Entry Point**: `public/index.php` - Main application entry
- **Configuration**: Routes defined in `src/Definitions/RoutingDefinitions.php`

### Model Architecture
- **Base Model**: `src/Models/Base.php` - Abstract class with CRUD operations
- **Features**: Built-in encryption for sensitive fields, fillable fields pattern
- **Concrete Models**: Extend Base class (User, Blog, Session)

### View System
- **View Class**: `src/Core/View.php` - Template rendering
- **Templates**: Located in `src/views/` with layout support
- **Layout**: Main layout in `resources/views/layout.php`

### Security Features
- **Encryption**: `src/Services/EncryptionService.php` using sodium
- **Password Hashing**: ARGON2ID for user passwords
- **CSRF Protection**: `src/Security/CSRFToken.php`

## Important Development Guidelines

### Third-Party Library Policy
**CRITICAL**: This project uses only raw PHP - no Composer third-party libraries in production code. Only PHPUnit and PHPStan are allowed as dev dependencies.

### Testing Structure
- Tests follow PSR-4 in `tests/` directory
- Uses PHP 8 attributes instead of PHPDoc annotations
- Comprehensive CRUD testing for models
- Mock objects for database operations

### Code Conventions
- Strict types declaration required: `declare(strict_types=1);`
- PSR-4 autoloading with `App\` namespace mapping to `src/`
- Interface-driven development where applicable

### Database Patterns
- **Migrations**: Place in `src/Migrations/` implementing `MigrationInterface`
- **Seeders**: Place in `src/Seeders/` for test data
- **Models**: Extend `Base` class, define `$table`, `$fillable`, `$encrypted` properties

### Controller Patterns
- Extend `src/Core/Controller.php` base class
- Return arrays with 'view' and 'data' keys for template rendering
- Use dependency injection via constructor

## Key File Locations

- **Bootstrap**: `bootstrap.php` - Container setup and service registration
- **Entry Point**: `public/index.php` - Request handling
- **Core Classes**: `src/Core/` - Router, Container, View, Controller base
- **Tools**: `tools/` - Migration and seeding scripts (secure from web access)
- **Configuration**: Environment variables in `.env` file (not committed)

## Development Workflow

1. Create/modify migrations in `src/Migrations/`
2. Run migrations with `php tools/migrations.php`
3. Create models extending `Base` class
4. Add controllers and register routes in `RoutingDefinitions`
5. Create corresponding views in `src/views/`
6. Write tests in `tests/` directory
7. Run tests and static analysis before committing

## Security Notes

- The `tools/` directory should not be web-accessible in production
- Environment file `.env` contains sensitive data and should not be committed
- All user input should be validated and sanitized
- Sensitive model fields are automatically encrypted via `$encrypted` array

## Project Constraints and Guidelines

### Language and Tooling
- We are using PHP 8.4 strict, we are not able to add any new third party libraries to composer, so we are writing raw PHP 8.4 code using TDD and all new code should be docmented and have PHP unit tests

## Agent Collaboration Guidelines

### Agent Coordination & Communication
- **Cross-Agent Requirements**: All agents MUST adhere to project constraints defined in this file
- **Security-First Approach**: OWASP security auditor recommendations override all other considerations
- **Code Quality Standards**: PHP QA testing expert validates all code before deployment
- **Documentation Requirements**: PHP MVC blog writer documents significant features and architectural decisions

### Agent Workflow Integration

1. **Development Flow**:
   - PHP MVC Expert: Designs architecture and implements features
   - OWASP Security Auditor: Reviews security implications BEFORE implementation
   - PHP QA Testing Expert: Creates comprehensive tests for all new code
   - PHP MVC Blog Writer: Documents features for developer education

2. **Quality Gates** (All agents must enforce):
   - `declare(strict_types=1)` in all PHP files
   - PHPStan level 9 compliance
   - Minimum 95% test coverage
   - OWASP security compliance
   - No third-party production dependencies

3. **Communication Protocol**:
   - Agents share context through code comments and documentation
   - Security vulnerabilities trigger immediate cross-agent alerts
   - Test failures block all further development
   - Documentation updates required for all public APIs

### Duplicate Code Prevention

1. **Code Reuse Strategy**:
   - Check existing services in `src/Services/` before creating new ones
   - Use base classes and traits for shared functionality
   - Implement DRY principle across all agent contributions
   - Review `src/Core/` components for existing utilities

2. **Pattern Consistency**:
   - Follow established patterns in `src/Models/Base.php`
   - Use dependency injection via `bootstrap.php` definitions
   - Maintain consistent error handling patterns
   - Reuse validation logic from `src/Services/UserValidationService.php`

### Security Requirements Enforcement

1. **Mandatory Security Checks** (All agents):
   - Input validation on all user data
   - Output encoding for XSS prevention
   - CSRF token validation on state-changing operations
   - SQL injection prevention through prepared statements
   - Authentication and authorization checks

2. **Security Standards**:
   - Encryption: X25519, Ed25519, and sodium library
   - Password: ARGON2ID hashing only
   - Sessions: Secure, httponly, samesite cookies
   - Rate limiting: Implement on all public endpoints
   - Logging: Security events to `SecurityLoggerService`

### Agent-Specific Responsibilities

#### PHP MVC Expert
- Ensure architectural consistency
- Implement secure design patterns
- Optimize performance while maintaining security
- Coordinate with other agents for holistic solutions

#### OWASP Security Auditor
- **VETO POWER**: Can block any code with security vulnerabilities
- Perform security reviews on ALL authentication code
- Validate encryption implementations
- Monitor for OWASP Top 10 vulnerabilities

#### PHP QA Testing Expert
- Enforce 95% minimum test coverage
- Validate PHPStan level 9 compliance
- Create security-focused test cases
- Ensure all code examples in documentation are tested

#### PHP MVC Blog Writer
- Document security best practices
- Create developer education content
- Ensure code examples follow all standards
- Update documentation with architectural decisions

### Conflict Resolution

1. **Priority Order** (highest to lowest):
   1. Security vulnerabilities (OWASP Security Auditor)
   2. Test failures (PHP QA Testing Expert)
   3. Architectural consistency (PHP MVC Expert)
   4. Documentation clarity (PHP MVC Blog Writer)

2. **Escalation Path**:
   - Security issues: Immediate halt and remediation
   - Quality issues: Block deployment until resolved
   - Architecture conflicts: Consensus through design review
   - Documentation gaps: Complete before feature release