<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Core\Container;

class ContainerTest extends TestCase
{
    public function testRegisterAndResolveService()
    {
        $container = new Container();
        $container->register('test_service', function () {
            return new stdClass();
        });

        $service = $container->resolve('test_service');
        $this->assertInstanceOf(stdClass::class, $service);
    }

    public function testSingletonService()
    {
        $container = new Container();

        // Registering the singleton service
        $container->register('singleton_service', function($c) {
            return new stdClass();
        }, true);

        // Resolving the singleton service twice
        $instance1 = $container->resolve('singleton_service');
        $instance2 = $container->resolve('singleton_service');

        // Asserting that both instances are the same
        $this->assertSame($instance1, $instance2);
    }

    public function testServiceNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service test_service not found.');

        $container = new Container();
        $container->resolve('test_service');
    }

    public function testAutowiring()
    {
        $container = new Container();
        $container->register(TestService::class, function (Container $c) {
            return new TestService();
        });
    
        $container->register(DependentService::class, function (Container $c) {
            return new DependentService($c->resolve(TestService::class));
        });
    
        $service = $container->resolve(DependentService::class);
        $this->assertInstanceOf(DependentService::class, $service);
        $this->assertInstanceOf(TestService::class, $service->getTestService());
    }
}

class TestService
{
    // Test service class
}

class DependentService
{
    private TestService $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    public function getTestService(): TestService
    {
        return $this->testService;
    }
}