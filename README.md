# Sample PHP MVC Project

## Project Overview

This project is a simple PHP MVC (Model-View-Controller) framework designed to demonstrate core PHP 8.3 functionality. The project avoids third-party libraries except for development tools such as PHPUnit, PHPStan, and PHPDoc. The purpose of this project is to create a lightweight and educational MVC framework that helps developers understand the fundamentals of PHP and MVC architecture without relying heavily on external dependencies.

### Key Features

- **Environment Configuration**: Environment variables are loaded using a custom `EnvLoader` class, allowing configuration to be managed via a `.env` file.
- **PDO Database Connection**: The project includes a `DatabaseService` class that manages the PDO connection, with methods to check database existence and query execution.
- **Testing with PHPUnit**: Unit tests have been written using PHPUnit to ensure that key components of the framework, such as the PDO connection, work correctly.
- **Encryption Service**: A service that provides encryption and decryption functionalities using the sodium library.
- **SetKey Utility**: A utility script for generating a sodium key for the user to manually save to the `.env` file.
- **Interfaces**: Interfaces are being added to enforce standard methods and promote consistency across the codebase.
- **Migration Example**: A `UserMigration` class that handles the creation and deletion of the users table in the database.
- **Seeder Example**: A `UserSeeder` class that uses the `User` model to import test data into the database.
- **Secure Password Generator**: A trait to generate secure passwords for the seeders.
- **Tools**: `seeders.php` and `migrations.php` scripts under the `tools` directory to run seeders and migrations.

## Warning

**Any pull requests that contain Composer third-party libraries will be rejected.** The aim of this project is to use raw PHP to create this framework, avoiding external dependencies to focus on understanding the core functionalities of PHP and MVC architecture.

## Disclaimer

**This code is currently not production ready. We are not responsible for any loss or breach incurred by using this sample code in a production environment. Always take extreme caution when dealing with real data in a production environment.**


## Progress

### Implemented Components

- **EnvLoader**: A class that loads environment variables from a `.env` file into the `$_ENV` superglobal.
- **DatabaseService**: A service that provides a PDO connection to the database, including a method to check if the database exists.
- **Unit Tests**: PHPUnit tests have been written to verify the functionality of the `DatabaseService`, including checking the PDO connection and database existence.
- **Encryption Service**: A service that provides encryption and decryption functionalities using the sodium library.
- **SetKey Utility**: A utility script for generating a sodium key for the user to manually save to the `.env` file.
- **Migration Example**: A `UserMigration` class that handles the creation and deletion of the users table in the database.
- **Seeder Example**: A `UserSeeder` class that uses the `User` model to import test data into the database.
- **Secure Password Generator**: A trait to generate secure passwords for the seeders.
- **Tools**: `seeders.php` and `migrations.php` scripts under the `tools` directory to run seeders and migrations.


### Next Steps

- **Models**: Implement models to interact with the database.
- **Views**: Create basic views to render HTML output.
- **Controllers**: Implement controllers to handle requests and manage application flow.
- **Routing**: Build a custom router to map URLs to controllers and actions.
- **Expand Test Coverage**: Write additional tests to cover new components as they are implemented.

## Running Migrations

To run the database migrations, execute the <span style="color:blue; font-weight:bold;">tools/migrations.php</span> script:

````sh
php tools/migrations.php
````

This will create the necessary tables in the database as defined in the migrations classes.

## Seeding the Database

To see the database with test data, run the <span style="color:blue; font-weight:bold;">tools/seeders.php</span> script:

````
php tools/seeders.php
````

This will insert test users into the database using the User model, which handles password hashing and encryption. 

Note: Ensure that the migrations have been run before seeding the database. If the migrations have not been run, you will receieve an error message indicating the migrations need to be run first. 

## Security Note

The <span style="color:blue; font-weight:bold;">tools</span> directory contains scripts that should not be accessible to anyone on the Internet. Ensure that this directory is protected and not exposed to the public. You can achive this by configuring your web server to deny access to the tools directory. 

## Testing Results

The project includes a test suite run using PHPUnit. The tests verify that the PDO connection is established successfully and that the database exists.


### PHPUnit Test Results

```bash
vendor/bin/phpunit --bootstrap vendor/autoload.php tests
PHPUnit 11.3.1 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.9
Configuration: Development/sample_php_code/phpunit.xml

................................                                  32 / 32 (100%)

Time: 00:00.835, Memory: 8.00 MB

OK (32 tests, 64 assertions)
````