# Sample PHP MVC Project

## Project Overview

This project is a simple PHP MVC (Model-View-Controller) framework designed to demonstrate core PHP 8.3 functionality. The project avoids third-party libraries except for development tools such as PHPUnit, PHPStan, and PHPDoc. The purpose of this project is to create a lightweight and educational MVC framework that helps developers understand the fundamentals of PHP and MVC architecture without relying heavily on external dependencies.

### Key Features

- **Custom Dependency Injection**: The project includes a simple, custom-built Dependency Injection (DI) container to manage service dependencies.
- **Environment Configuration**: Environment variables are loaded using a custom `EnvLoader` class, allowing configuration to be managed via a `.env` file.
- **PDO Database Connection**: The project includes a `DatabaseService` class that manages the PDO connection, with methods to check database existence and query execution.
- **Testing with PHPUnit**: Unit tests have been written using PHPUnit to ensure that key components of the framework, such as the PDO connection, work correctly.

## Progress

### Implemented Components

- **EnvLoader**: A class that loads environment variables from a `.env` file into the `$_ENV` superglobal.
- **DatabaseService**: A service that provides a PDO connection to the database, including a method to check if the database exists.
- **Custom DI Container**: A basic dependency injection container that registers and resolves services.
- **Unit Tests**: PHPUnit tests have been written to verify the functionality of the `DatabaseService`, including checking the PDO connection and database existence.

### Next Steps

- **Models**: Implement models to interact with the database.
- **Views**: Create basic views to render HTML output.
- **Controllers**: Implement controllers to handle requests and manage application flow.
- **Routing**: Build a custom router to map URLs to controllers and actions.
- **Expand Test Coverage**: Write additional tests to cover new components as they are implemented.

## Testing Results

The project includes a test suite run using PHPUnit. The tests verify that the PDO connection is established successfully and that the database exists.

### PHPUnit Test Results

```bash
sample_php_code % vendor/bin/phpunit --bootstrap vendor/autoload.php tests
PHPUnit 11.3.1 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.9
Configuration: /Users/michaelkingsnorth/Development/sample_php_code/phpunit.xml

..........                                                        10 / 10 (100%)

Time: 00:00.010, Memory: 8.00 MB

OK (10 tests, 11 assertions)
