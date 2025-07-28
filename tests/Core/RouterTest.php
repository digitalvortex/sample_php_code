<?php

declare(strict_types=1);

namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use App\Core\Router;
use App\Core\Container;
use App\Core\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * Test controller classes for Router testing
 */
class TestController
{
    public function show(array $params = []): string
    {
        if (!empty($params)) {
            return 'Post ' . ($params['id'] ?? 'unknown');
        }
        return 'Test Response';
    }

    public function showPost(array $params = []): string
    {
        $userId = $params['userId'] ?? 'unknown';
        $postId = $params['postId'] ?? 'unknown';
        return "User {$userId} Post {$postId}";
    }

    public function returnArray(): array
    {
        return [
            'view' => 'errors/404', // Use existing view to avoid warnings
            'data' => ['title' => 'Test Title', 'includeLayout' => false]
        ];
    }

    public function nonExistentMethod(): string
    {
        return 'This method should not be called';
    }
}

class UserController
{
    public function showPost(array $params = []): string
    {
        $userId = $params['userId'] ?? 'unknown';
        $postId = $params['postId'] ?? 'unknown';
        return "User {$userId} Post {$postId}";
    }
}

#[CoversClass(Router::class)]
final class RouterTest extends TestCase
{
    private Router $router;
    private Container|MockObject $containerMock;

    protected function setUp(): void
    {
        $this->containerMock = $this->createMock(Container::class);
        $this->router = new Router($this->containerMock);
        
        // Prevent actual output during tests
        ob_start();
    }

    /**
     * Mock the View::render static method for testing
     */
    private function mockViewRender(string $expectedView, array $expectedData, string $returnValue): void
    {
        // Since we can't easily mock static methods in PHPUnit 11,
        // we'll capture the output and verify it doesn't contain full HTML
        // The Router will handle View rendering, but our tests will focus on
        // the controller response before it gets to View::render
    }

    protected function tearDown(): void
    {
        // Clean up output buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    #[Test]
    #[TestDox('Can add simple routes without parameters')]
    public function testAddSimpleRoute(): void
    {
        $this->router->addRoute('GET', '/', 'HomeController@show');
        $this->router->addRoute('POST', '/contact', 'ContactController@submit');

        $routes = $this->router->getRoutes();
        
        $this->assertCount(2, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/', $routes[0]['path']);
        $this->assertEquals('HomeController@show', $routes[0]['handler']);
        $this->assertArrayNotHasKey('pattern', $routes[0]);
    }

    #[Test]
    #[TestDox('Can add routes with parameters')]
    public function testAddRouteWithParameters(): void
    {
        $this->router->addRoute('GET', '/blog/{id}', 'BlogController@show');
        $this->router->addRoute('GET', '/user/{id}/post/{postId}', 'UserController@showPost');

        $routes = $this->router->getRoutes();
        
        $this->assertCount(2, $routes);
        $this->assertEquals('/blog/{id}', $routes[0]['path']);
        $this->assertArrayHasKey('pattern', $routes[0]);
        $this->assertEquals('#^\/blog\/(?P<id>[^\/]+)$#', $routes[0]['pattern']);
    }

    #[Test]
    #[TestDox('Throws exception for empty method')]
    public function testAddRouteThrowsExceptionForEmptyMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method, path, and handler cannot be empty');
        
        $this->router->addRoute('', '/test', 'TestController@show');
    }

    #[Test]
    #[TestDox('Throws exception for empty path')]
    public function testAddRouteThrowsExceptionForEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method, path, and handler cannot be empty');
        
        $this->router->addRoute('GET', '', 'TestController@show');
    }

    #[Test]
    #[TestDox('Throws exception for empty handler')]
    public function testAddRouteThrowsExceptionForEmptyHandler(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method, path, and handler cannot be empty');
        
        $this->router->addRoute('GET', '/test', '');
    }

    #[Test]
    #[TestDox('Throws exception for invalid handler format')]
    public function testAddRouteThrowsExceptionForInvalidHandler(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Handler must be in format Controller@method');
        
        $this->router->addRoute('GET', '/test', 'TestController');
    }

    #[Test]
    #[TestDox('Can dispatch simple routes')]
    public function testDispatchSimpleRoute(): void
    {
        $testController = new TestController();

        $this->containerMock->expects($this->once())
                           ->method('get')
                           ->with('Tests\\Core\\TestController')
                           ->willReturn($testController);

        $this->router->addRoute('GET', '/test', 'Tests\\Core\\TestController@show');
        $this->router->dispatch('GET', '/test');

        $output = ob_get_contents();
        $this->assertEquals('Test Response', $output);
    }

    #[Test]
    #[TestDox('Can dispatch routes with parameters')]
    public function testDispatchRouteWithParameters(): void
    {
        $testController = new TestController();

        $this->containerMock->expects($this->once())
                           ->method('get')
                           ->with('Tests\\Core\\TestController')
                           ->willReturn($testController);

        $this->router->addRoute('GET', '/blog/{id}', 'Tests\\Core\\TestController@show');
        $this->router->dispatch('GET', '/blog/123');

        $output = ob_get_contents();
        $this->assertEquals('Post 123', $output);
    }

    #[Test]
    #[TestDox('Can dispatch routes with multiple parameters')]
    public function testDispatchRouteWithMultipleParameters(): void
    {
        $userController = new UserController();

        $this->containerMock->expects($this->once())
                           ->method('get')
                           ->with('Tests\\Core\\UserController')
                           ->willReturn($userController);

        $this->router->addRoute('GET', '/user/{userId}/post/{postId}', 'Tests\\Core\\UserController@showPost');
        $this->router->dispatch('GET', '/user/456/post/789');

        $output = ob_get_contents();
        $this->assertEquals('User 456 Post 789', $output);
    }

    #[Test]
    #[TestDox('Handles array response from controller')]
    public function testDispatchHandlesArrayResponse(): void
    {
        $testController = new TestController();

        $this->containerMock->expects($this->once())
                           ->method('get')
                           ->with('Tests\\Core\\TestController')
                           ->willReturn($testController);

        $this->router->addRoute('GET', '/test', 'Tests\\Core\\TestController@returnArray');
        $this->router->dispatch('GET', '/test');

        $output = ob_get_contents();
        // Since View::render will be called, we expect some output (not empty)
        // but we can't predict the exact content since it depends on view files
        $this->assertNotEmpty($output);
        // Verify that no exception was thrown during dispatch
        $this->assertTrue(true);
    }

    #[Test]
    #[TestDox('Returns 404 for non-existent routes')]
    public function testDispatchReturns404ForNonExistentRoute(): void
    {
        $this->router->addRoute('GET', '/existing', 'TestController@show');
        $this->router->dispatch('GET', '/non-existent');

        // Verify that a 404 response was sent
        $this->assertEquals(404, http_response_code());
    }

    #[Test]
    #[TestDox('Returns 404 for wrong HTTP method')]
    public function testDispatchReturns404ForWrongMethod(): void
    {
        $this->router->addRoute('GET', '/test', 'TestController@show');
        $this->router->dispatch('POST', '/test');

        $this->assertEquals(404, http_response_code());
    }

    #[Test]
    #[TestDox('Handles controller class not found')]
    public function testDispatchHandlesControllerNotFound(): void
    {
        // The router will check if the class exists before calling container->get
        $this->router->addRoute('GET', '/test', 'NonExistentController@show');
        $this->router->dispatch('GET', '/test');

        $this->assertEquals(500, http_response_code());
    }

    #[Test]
    #[TestDox('Handles method not found in controller')]
    public function testDispatchHandlesMethodNotFound(): void
    {
        $testController = new TestController();
        
        $this->containerMock->expects($this->once())
                           ->method('get')
                           ->with('Tests\\Core\\TestController')
                           ->willReturn($testController);

        $this->router->addRoute('GET', '/test', 'Tests\\Core\\TestController@methodThatDoesNotExist');
        $this->router->dispatch('GET', '/test');

        $this->assertEquals(500, http_response_code());
    }

    #[Test]
    #[TestDox('Method names are case insensitive')]
    public function testDispatchMethodCaseInsensitive(): void
    {
        $testController = new TestController();

        $this->containerMock->expects($this->once())
                           ->method('get')
                           ->with('Tests\\Core\\TestController')
                           ->willReturn($testController);

        $this->router->addRoute('get', '/test', 'Tests\\Core\\TestController@show');
        $this->router->dispatch('GET', '/test');

        $output = ob_get_contents();
        $this->assertEquals('Test Response', $output);
    }

    #[Test]
    #[TestDox('Can get all registered routes')]
    public function testGetRoutes(): void
    {
        $this->router->addRoute('GET', '/', 'HomeController@show');
        $this->router->addRoute('POST', '/contact', 'ContactController@submit');
        $this->router->addRoute('GET', '/blog/{id}', 'BlogController@show');

        $routes = $this->router->getRoutes();
        
        $this->assertCount(3, $routes);
        $this->assertArrayHasKey('method', $routes[0]);
        $this->assertArrayHasKey('path', $routes[0]);
        $this->assertArrayHasKey('handler', $routes[0]);
    }

    #[Test]
    #[TestDox('Handles special characters in route parameters safely')]
    public function testDispatchHandlesSpecialCharactersInParameters(): void
    {
        $testController = new TestController();

        $this->containerMock->expects($this->once())
                           ->method('get')
                           ->with('Tests\\Core\\TestController')
                           ->willReturn($testController);

        $this->router->addRoute('GET', '/blog/{id}', 'Tests\\Core\\TestController@show');
        $this->router->dispatch('GET', '/blog/test-123');

        $output = ob_get_contents();
        $this->assertEquals('Post test-123', $output);
    }

    #[Test]
    #[TestDox('Route matching is exact and does not allow path traversal')]
    public function testDispatchPreventsPathTraversal(): void
    {
        $this->router->addRoute('GET', '/safe/path', 'TestController@show');
        $this->router->dispatch('GET', '/safe/../unsafe');

        $this->assertEquals(404, http_response_code());
    }

    #[Test]
    #[TestDox('Route parameters cannot contain forward slashes')]
    public function testRouteParametersDoNotAllowSlashes(): void
    {
        $this->router->addRoute('GET', '/user/{id}', 'TestController@show');
        $this->router->dispatch('GET', '/user/123/extra');

        $this->assertEquals(404, http_response_code());
    }

    #[Test]
    #[TestDox('Empty route parameters are handled gracefully')]
    public function testDispatchHandlesEmptyParameters(): void
    {
        $this->router->addRoute('GET', '/blog/{id}', 'TestController@show');
        $this->router->dispatch('GET', '/blog/');

        $this->assertEquals(404, http_response_code());
    }

}