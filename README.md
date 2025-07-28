# Sample PHP MVC Project

## Project Overview

This project is a modern PHP MVC (Model-View-Controller) framework designed to demonstrate core PHP 8.4 functionality with strict typing and contemporary web development practices. The project avoids third-party libraries except for development tools such as PHPUnit, PHPStan, and PHPDoc. The purpose of this project is to create a lightweight and educational MVC framework that helps developers understand the fundamentals of modern PHP and MVC architecture without relying heavily on external dependencies.

**✨ Recent Major Update**: Complete modern website redesign with responsive design, professional UI/UX, and comprehensive frontend enhancements.

### Key Features

#### 🏗️ **Core Framework**
- **PHP 8.4 Compliance**: Strict typing, modern syntax, and latest PHP features
- **MVC Architecture**: Clean separation of concerns with dependency injection
- **Advanced Routing**: Route parameters, pattern matching, and centralized definitions
- **Security-First**: CSRF protection, input validation, encryption services
- **Environment Configuration**: Custom `EnvLoader` class with `.env` file support

#### 🎨 **Modern Frontend**
- **Responsive Design**: Mobile-first approach with CSS Grid and Flexbox
- **Design System**: Comprehensive CSS variables, components, and utilities
- **Professional UI/UX**: Modern typography, animations, and micro-interactions
- **Accessibility**: ARIA labels, skip links, keyboard navigation, screen reader support
- **Performance**: Optimized animations with Intersection Observer API

#### 🗄️ **Database & Models**
- **PDO Database Connection**: Robust `DatabaseService` with connection management
- **Base Model**: Abstract model with CRUD operations and encryption support
- **Migration System**: Database schema management with versioning
- **Seeder System**: Test data generation with secure password hashing
- **Soft Deletes**: Non-destructive record deletion with recovery options

#### 🧪 **Testing & Quality**
- **PHPUnit 11**: Comprehensive test suite with PHP 8.4 attributes
- **77 Tests, 188 Assertions**: Extensive coverage of all components
- **Quality Tools**: PHPStan, PHPDoc, and development tooling
- **Test-Driven Development**: Robust testing patterns and best practices

#### 🔧 **Development Tools**
- **Migration Runner**: `tools/migrations.php` for database schema management
- **Seeder Runner**: `tools/seeders.php` for test data generation
- **Encryption Utilities**: Sodium-based encryption with key generation
- **Development Commands**: Comprehensive tooling for development workflow

## Warning

**Any pull requests that contain Composer third-party libraries will be rejected.** The aim of this project is to use raw PHP to create this framework, avoiding external dependencies to focus on understanding the core functionalities of PHP and MVC architecture.

## Disclaimer

**This code is currently not production ready. We are not responsible for any loss or breach incurred by using this sample code in a production environment. Always take extreme caution when dealing with real data in a production environment.**

## Progress

### Implemented Components

#### 🏗️ **Core Architecture**
- **Advanced Router**: Enhanced with route parameters (`/blog/{id}`), pattern matching, error handling, and security features
- **Dependency Injection Container**: Autowiring and container-based service resolution
- **Routing Definitions**: Centralized route configuration with `/src/Definitions/RoutingDefinitions.php`
- **Base Controller**: Abstract controller with common functionality and dependency injection
- **View System**: Modern view rendering with layout support and data passing

#### 🎯 **Controllers (All PHP 8.4 Compliant)**
- **HomeController**: Landing page with modern hero section and feature showcase
- **AboutController**: Company information with values and technical expertise
- **ServicesController**: Professional services showcase with process timeline
- **ContactController**: Enhanced contact form with validation and CSRF protection
- **BlogController**: Blog listing with pagination and modern card layouts
- **ErrorController**: Professional 404/500 error pages with helpful navigation

#### 🎨 **Modern Frontend Design**
- **Responsive Layouts**: All pages redesigned with mobile-first approach
- **Design System**: 982 lines of modern CSS with comprehensive variable system
- **Component Library**: Reusable cards, buttons, forms, grids, and animations
- **Professional Typography**: Inter font with fluid typography scales
- **Interactive Features**: Enhanced JavaScript with scroll effects, form enhancements, and mobile navigation

#### 📱 **Page Designs**
- **Home Page**: Hero section, feature cards, technology showcase, call-to-action
- **About Page**: Story section, values cards, technical expertise showcase
- **Services Page**: Service cards, process timeline, technology stack, pricing
- **Contact Page**: Two-column layout with contact info and enhanced form
- **Blog Page**: Modern grid layout with metadata, tags, and newsletter signup
- **Error Pages**: Professional 404/500 designs with helpful navigation and technical details

#### 🗄️ **Data Layer**
- **Enhanced Models**: Base model with encryption, soft deletes, and CRUD operations
- **Database Migrations**: User migration with proper schema management
- **Seeders**: Secure test data generation with ARGON2ID password hashing
- **Database Service**: Robust PDO connection management with error handling

#### 🔒 **Security Features**
- **Encryption Service**: Sodium-based encryption for sensitive data
- **CSRF Protection**: Token-based protection for forms
- **Input Validation**: Comprehensive validation with error handling
- **Password Security**: ARGON2ID hashing with secure parameter generation
- **SQL Injection Prevention**: Prepared statements throughout

#### 🧪 **Testing Infrastructure**
- **77 PHPUnit Tests**: Comprehensive test coverage with 188 assertions
- **PHP 8.4 Attributes**: Modern test attributes replacing old annotations
- **Router Testing**: 20 comprehensive router tests including parameter handling
- **Model Testing**: CRUD operations, encryption, validation, and edge cases
- **TestDox Documentation**: Human-readable test descriptions

### Recent Major Updates

#### 🎨 **Complete Website Redesign (Latest)**
- **Modern UI/UX**: Professional design with contemporary aesthetics and user experience
- **Responsive Design System**: Comprehensive CSS variables, grid system, and mobile-first approach
- **Enhanced All Pages**: Home, About, Services, Contact, Blog, and Error pages completely redesigned
- **Interactive Elements**: Smooth animations, micro-interactions, and enhanced form experiences
- **Accessibility Improvements**: ARIA labels, skip links, keyboard navigation, and screen reader support

#### 🏗️ **Routing & Architecture Enhancements**
- **Advanced Router**: Route parameters support (`/blog/{id}`), pattern compilation, and security features
- **Centralized Routing**: `RoutingDefinitions.php` for organized route management
- **Enhanced Controllers**: All controllers updated with PHP 8.4 strict typing and dependency injection
- **Error Handling**: Comprehensive error pages with professional designs and helpful navigation

#### 🔒 **Security & Quality Improvements**
- **PHP 8.4 Compliance**: Strict typing throughout the codebase with modern PHP features
- **Enhanced Testing**: 77 tests with 188 assertions, comprehensive router and model coverage
- **Security First**: CSRF protection, input validation, and encrypted data handling
- **Code Quality**: Consistent patterns, interfaces, and dependency injection throughout

### Next Steps

#### 🔄 **Immediate Enhancements**
- **Contact Form Processing**: Complete backend form submission and email handling
- **Blog Content Management**: Add blog post creation, editing, and management functionality
- **Database Content**: Populate with real blog posts and dynamic content

#### 🏗️ **Advanced Features**
- **Authentication System**: User registration, login, and session management
- **Authorization**: Role-based access control and permissions
- **Admin Panel**: Content management interface for blog posts and site settings
- **API Endpoints**: RESTful API for mobile apps and third-party integrations

#### 🚀 **Scalability & Performance**
- **Caching Layer**: Redis/Memcached integration for improved performance
- **Rate Limiting**: Request throttling and abuse prevention
- **Model Relationships**: Advanced ORM features with foreign keys and joins
- **Model Events & Observers**: Lifecycle hooks and observer patterns

#### 🛍️ **Extended Functionality**
- **E-commerce Module**: Basic shopping cart and product management
- **Newsletter System**: Email subscription and campaign management
- **GeoIP Integration**: Location-based features and analytics
- **Middleware System**: Request/response filtering and preprocessing
- **File Upload System**: Image and document handling with validation

#### ⚡ **Advanced Topics**
- **WebSocket Support**: Real-time features and live updates
- **Queue System**: Background job processing
- **Search Functionality**: Full-text search with indexing
- **Multi-language Support**: Internationalization and localization
- **Progressive Web App**: Service workers and offline functionality

## Running Migrations

To run the database migrations, execute the <span style="color:blue; font-weight:bold;">tools/migrations.php</span> script:

```sh
php tools/migrations.php
```

This will create the necessary tables in the database as defined in the migrations classes.

## Seeding the Database

To see the database with test data, run the <span style="color:blue; font-weight:bold;">tools/seeders.php</span> script:

```
php tools/seeders.php
```

This will insert test users into the database using the User model, which handles password hashing and encryption.

Note: Ensure that the migrations have been run before seeding the database. If the migrations have not been run, you will receieve an error message indicating the migrations need to be run first.

## Security Note

The <span style="color:blue; font-weight:bold;">tools</span> directory contains scripts that should not be accessible to anyone on the Internet. Ensure that this directory is protected and not exposed to the public. You can achive this by configuring your web server to deny access to the tools directory.

## Testing Results

The project includes a test suite run using PHPUnit. The tests verify that the PDO connection is established successfully and that the database exists.

### PHPUnit Test Results

```bash
vendor/bin/phpunit --bootstrap vendor/autoload.php tests
PHPUnit 11.4.3 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.2
Configuration: /Users/michaelkingsnorth/Development/sample_php_code/phpunit.xml

.............................................................................         77 / 77 (100%)

Time: 00:02.456, Memory: 12.00 MB

OK (77 tests, 188 assertions)
```

**Test Coverage Highlights:**
- ✅ **Router Tests**: 20 comprehensive tests covering route parameters, security, and error handling
- ✅ **Model Tests**: CRUD operations, encryption, validation, and soft deletes
- ✅ **Controller Tests**: Dependency injection, response handling, and error management
- ✅ **Service Tests**: Database connections, encryption, validation, and utilities
- ✅ **Integration Tests**: End-to-end testing of critical workflows

## 🚀 Getting Started

### Prerequisites
- PHP 8.4 or higher
- MySQL/MariaDB database
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd sample_php_code
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Generate encryption key**
   ```bash
   php tools/setkey.php
   ```

4. **Run migrations**
   ```bash
   php tools/migrations.php
   ```

5. **Seed database (optional)**
   ```bash
   php tools/seeders.php
   ```

6. **Start development server**
   ```bash
   php -S localhost:8000 -t public
   ```

7. **Visit your site**
   Open `http://localhost:8000` in your browser

### Running Tests
```bash
vendor/bin/phpunit --bootstrap vendor/autoload.php tests
```

## 📁 Project Structure

```
sample_php_code/
├── public/                 # Web-accessible files
│   ├── css/main.css       # Modern design system
│   ├── js/main.js         # Interactive enhancements
│   └── index.php          # Entry point
├── src/
│   ├── Controllers/       # MVC Controllers
│   ├── Models/           # Data models
│   ├── Core/             # Framework core
│   ├── Services/         # Business logic
│   ├── Definitions/      # Route definitions
│   └── views/            # Templates and layouts
├── tests/                # PHPUnit test suite
├── tools/                # Development utilities
└── vendor/               # Composer dependencies
```
