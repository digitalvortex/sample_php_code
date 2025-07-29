<?php

declare(strict_types=1);

namespace Tests;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Interface Compliance Test
 * 
 * Comprehensive validation of all interface implementations for PHP 8.4 compatibility.
 * Tests method signatures, return types, and parameter types.
 */
class InterfaceComplianceTest
{
    private array $results = [];
    private array $errors = [];

    /**
     * Map of interfaces to their implementing classes (only existing classes)
     */
    private array $interfaceMap = [
        'App\Interfaces\ContainerInterface' => ['App\Core\Container'],
        'App\Interfaces\EncryptionInterface' => ['App\Services\EncryptionService'],
        'App\Interfaces\SecurityInterface' => ['App\Security\CSRFToken'],
        'App\Interfaces\ValidationInterface' => ['App\Response\ValidationResponse'],
        'App\Interfaces\ModelInterface' => ['App\Models\Base'],
        'App\Interfaces\ErrorHandlerInterface' => ['App\Core\ErrorHandler'],
        'App\Interfaces\DatabaseDefinitionsInterface' => ['App\Definitions\DatabaseDefinitions'],
        'App\Interfaces\ControllerInterface' => [
            'App\Controllers\HomeController',
            'App\Controllers\AboutController',
            'App\Controllers\BlogController',
            'App\Controllers\BlogAdminController',
            'App\Controllers\ServicesController'
        ],
        'App\Interfaces\ErrorControllerInterface' => ['App\Controllers\ErrorController'],
        'App\Interfaces\MiddlewareInterface' => [
            'App\Middleware\CSRFMiddleware',
            'App\Middleware\AuthenticationMiddleware',
            'App\Middleware\RateLimitMiddleware',
            'App\Middleware\CorsMiddleware',
            'App\Middleware\LoggingMiddleware',
            'App\Middleware\CachingMiddleware'
        ],
        'App\Interfaces\ResponseInterface' => ['App\Core\Response'],
        'App\Interfaces\RouterInterface' => ['App\Core\Router'],
    ];

    public function runAllTests(): array
    {
        echo "🔍 Starting Interface Compliance Tests for PHP 8.4...\n\n";

        foreach ($this->interfaceMap as $interface => $implementations) {
            foreach ($implementations as $implementation) {
                $this->testInterfaceImplementation($interface, $implementation);
            }
        }

        $this->generateReport();
        return $this->results;
    }

    private function testInterfaceImplementation(string $interface, string $implementation): void
    {
        echo "Testing: {$implementation} implements {$interface}\n";

        try {
            // Check if classes exist
            if (!interface_exists($interface)) {
                $this->addError($implementation, "Interface {$interface} does not exist");
                return;
            }

            if (!class_exists($implementation)) {
                $this->addError($implementation, "Class {$implementation} does not exist");
                return;
            }

            $interfaceReflection = new ReflectionClass($interface);
            $classReflection = new ReflectionClass($implementation);

            // Check if class implements interface
            if (!$classReflection->implementsInterface($interface)) {
                $this->addError($implementation, "Class does not implement interface {$interface}");
                return;
            }

            // Test each interface method
            foreach ($interfaceReflection->getMethods() as $interfaceMethod) {
                $this->testMethodImplementation($interfaceMethod, $classReflection, $implementation);
            }

            $this->addSuccess($implementation, "Successfully implements {$interface}");

        } catch (\Throwable $e) {
            $this->addError($implementation, "Exception during testing: " . $e->getMessage());
        }
    }

    private function testMethodImplementation(ReflectionMethod $interfaceMethod, ReflectionClass $classReflection, string $implementation): void
    {
        $methodName = $interfaceMethod->getName();

        try {
            if (!$classReflection->hasMethod($methodName)) {
                $this->addError($implementation, "Missing method: {$methodName}");
                return;
            }

            $classMethod = $classReflection->getMethod($methodName);

            // Test method visibility
            if ($interfaceMethod->isPublic() && !$classMethod->isPublic()) {
                $this->addError($implementation, "Method {$methodName} should be public");
            }

            // Test static methods
            if ($interfaceMethod->isStatic() !== $classMethod->isStatic()) {
                $staticType = $interfaceMethod->isStatic() ? 'static' : 'non-static';
                $this->addError($implementation, "Method {$methodName} should be {$staticType}");
            }

            // Test return types
            $this->testReturnType($interfaceMethod, $classMethod, $implementation, $methodName);

            // Test parameters
            $this->testParameters($interfaceMethod, $classMethod, $implementation, $methodName);

        } catch (\Throwable $e) {
            $this->addError($implementation, "Error testing method {$methodName}: " . $e->getMessage());
        }
    }

    private function testReturnType(ReflectionMethod $interfaceMethod, ReflectionMethod $classMethod, string $implementation, string $methodName): void
    {
        $interfaceReturnType = $interfaceMethod->getReturnType();
        $classReturnType = $classMethod->getReturnType();

        if ($interfaceReturnType === null && $classReturnType === null) {
            return; // Both have no return type
        }

        if ($interfaceReturnType === null && $classReturnType !== null) {
            $this->addWarning($implementation, "Method {$methodName} has return type but interface doesn't specify one");
            return;
        }

        if ($interfaceReturnType !== null && $classReturnType === null) {
            $this->addError($implementation, "Method {$methodName} missing return type: " . $interfaceReturnType);
            return;
        }

        // Both have return types - check compatibility
        $interfaceType = $interfaceReturnType->__toString();
        $classType = $classReturnType->__toString();

        if ($interfaceType !== $classType) {
            // Check for compatible types (mixed is compatible with anything)
            if ($interfaceType !== 'mixed' && $classType !== 'mixed') {
                $this->addError($implementation, "Method {$methodName} return type mismatch. Expected: {$interfaceType}, Got: {$classType}");
            }
        }
    }

    private function testParameters(ReflectionMethod $interfaceMethod, ReflectionMethod $classMethod, string $implementation, string $methodName): void
    {
        $interfaceParams = $interfaceMethod->getParameters();
        $classParams = $classMethod->getParameters();

        if (count($interfaceParams) !== count($classParams)) {
            $this->addError($implementation, "Method {$methodName} parameter count mismatch. Expected: " . count($interfaceParams) . ", Got: " . count($classParams));
            return;
        }

        foreach ($interfaceParams as $index => $interfaceParam) {
            $classParam = $classParams[$index];
            $this->testParameter($interfaceParam, $classParam, $implementation, $methodName);
        }
    }

    private function testParameter(ReflectionParameter $interfaceParam, ReflectionParameter $classParam, string $implementation, string $methodName): void
    {
        $paramName = $interfaceParam->getName();

        // Test parameter names
        if ($interfaceParam->getName() !== $classParam->getName()) {
            $this->addWarning($implementation, "Method {$methodName} parameter name mismatch: {$paramName} vs {$classParam->getName()}");
        }

        // Test parameter types
        $interfaceType = $interfaceParam->getType();
        $classType = $classParam->getType();

        if ($interfaceType !== null && $classType !== null) {
            $interfaceTypeName = $interfaceType->__toString();
            $classTypeName = $classType->__toString();

            if ($interfaceTypeName !== $classTypeName) {
                $this->addError($implementation, "Method {$methodName} parameter {$paramName} type mismatch. Expected: {$interfaceTypeName}, Got: {$classTypeName}");
            }
        } elseif ($interfaceType !== null && $classType === null) {
            $this->addError($implementation, "Method {$methodName} parameter {$paramName} missing type: " . $interfaceType);
        }

        // Test optional parameters
        if ($interfaceParam->isOptional() !== $classParam->isOptional()) {
            $optionalType = $interfaceParam->isOptional() ? 'optional' : 'required';
            $this->addError($implementation, "Method {$methodName} parameter {$paramName} should be {$optionalType}");
        }
    }

    private function addSuccess(string $class, string $message): void
    {
        $this->results[$class]['success'][] = $message;
        echo "  ✅ {$message}\n";
    }

    private function addError(string $class, string $message): void
    {
        $this->results[$class]['errors'][] = $message;
        $this->errors[] = "{$class}: {$message}";
        echo "  ❌ {$message}\n";
    }

    private function addWarning(string $class, string $message): void
    {
        $this->results[$class]['warnings'][] = $message;
        echo "  ⚠️  {$message}\n";
    }

    private function generateReport(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🔍 INTERFACE COMPLIANCE TEST REPORT\n";
        echo str_repeat("=", 80) . "\n\n";

        $totalClasses = count($this->results);
        $errorClasses = 0;
        $warningClasses = 0;
        $successClasses = 0;

        foreach ($this->results as $class => $result) {
            $hasErrors = !empty($result['errors']);
            $hasWarnings = !empty($result['warnings']);

            if ($hasErrors) {
                $errorClasses++;
            } elseif ($hasWarnings) {
                $warningClasses++;
            } else {
                $successClasses++;
            }
        }

        echo "📊 SUMMARY:\n";
        echo "  Total Classes Tested: {$totalClasses}\n";
        echo "  ✅ Fully Compliant: {$successClasses}\n";
        echo "  ⚠️  With Warnings: {$warningClasses}\n";
        echo "  ❌ With Errors: {$errorClasses}\n\n";

        if (!empty($this->errors)) {
            echo "🚨 CRITICAL ISSUES FOUND:\n";
            foreach ($this->errors as $error) {
                echo "  • {$error}\n";
            }
            echo "\n";
        }

        // Detailed results
        foreach ($this->results as $class => $result) {
            $status = "✅";
            if (!empty($result['errors'])) {
                $status = "❌";
            } elseif (!empty($result['warnings'])) {
                $status = "⚠️";
            }

            echo "{$status} {$class}\n";

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    echo "    ❌ {$error}\n";
                }
            }

            if (!empty($result['warnings'])) {
                foreach ($result['warnings'] as $warning) {
                    echo "    ⚠️  {$warning}\n";
                }
            }

            echo "\n";
        }

        $overallStatus = empty($this->errors) ? "✅ PASSED" : "❌ FAILED";
        echo "🎯 OVERALL STATUS: {$overallStatus}\n";
        
        if (!empty($this->errors)) {
            echo "\n⚡ Action Required: Fix the critical issues above before deployment.\n";
        } else {
            echo "\n🎉 All interface implementations are PHP 8.4 compliant!\n";
        }
        
        echo str_repeat("=", 80) . "\n";
    }
}

// Auto-run if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $tester = new InterfaceComplianceTest();
    $results = $tester->runAllTests();
    
    // Exit with error code if there are critical issues
    $hasErrors = false;
    foreach ($results as $result) {
        if (!empty($result['errors'])) {
            $hasErrors = true;
            break;
        }
    }
    
    exit($hasErrors ? 1 : 0);
}