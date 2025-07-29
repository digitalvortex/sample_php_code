<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Controllers\LanguageController;
use App\Interfaces\LocalizationServiceInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use App\Core\Request;
use App\Core\Response;

/**
 * LanguageControllerTest
 * 
 * Comprehensive tests for language switching functionality and URL manipulation
 * to identify and fix double locale prefix issues.
 */
class LanguageControllerTest extends TestCase
{
    private LanguageController $controller;
    private LocalizationServiceInterface|MockObject $localizationService;
    private RequestInterface|MockObject $request;
    private ResponseInterface|MockObject $response;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the localization service
        $this->localizationService = $this->createMock(LocalizationServiceInterface::class);
        $this->localizationService
            ->method('getSupportedLocales')
            ->willReturn(['en', 'fr', 'es', 'de', 'it']);
        
        // Create controller instance
        $this->controller = new LanguageController($this->localizationService);
        
        // Mock request and response
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        
        // Setup environment variables for testing
        $_ENV['LOCALIZATION_COOKIE_NAME'] = 'locale';
        $_ENV['LOCALIZATION_LOCALE_IN_URL'] = true;
        $_ENV['LOCALIZATION_HIDE_DEFAULT_LOCALE'] = false;
        $_ENV['LOCALIZATION_DEFAULT_LOCALE'] = 'en';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up session if started
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Clean up server variables
        unset($_SERVER['HTTP_REFERER']);
    }

    /**
     * Test URL manipulation methods using reflection to access private methods
     */
    private function callPrivateMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * @dataProvider removeLocaleFromUrlProvider
     */
    public function testRemoveLocaleFromUrl(string $inputUrl, string $expectedUrl): void
    {
        $result = $this->callPrivateMethod($this->controller, 'removeLocaleFromUrl', [$inputUrl]);
        $this->assertEquals($expectedUrl, $result, "Failed to properly remove locale from URL: $inputUrl");
    }

    public function removeLocaleFromUrlProvider(): array
    {
        return [
            // Basic locale removal
            ['http://localhost:8085/fr/', 'http://localhost:8085/'],
            ['http://localhost:8085/en/about', 'http://localhost:8085/about'],
            ['http://localhost:8085/es/services', 'http://localhost:8085/services'],
            
            // Double locale scenarios (current bug)
            ['http://localhost:8085/fr/en', 'http://localhost:8085/en'],
            ['http://localhost:8085/en/fr/about', 'http://localhost:8085/fr/about'],
            
            // URLs without locale
            ['http://localhost:8085/', 'http://localhost:8085/'],
            ['http://localhost:8085/about', 'http://localhost:8085/about'],
            ['http://localhost:8085/contact', 'http://localhost:8085/contact'],
            
            // URLs with query parameters
            ['http://localhost:8085/fr/blog?page=2', 'http://localhost:8085/blog?page=2'],
            ['http://localhost:8085/en/search?q=test', 'http://localhost:8085/search?q=test'],
            
            // URLs with fragments
            ['http://localhost:8085/fr/about#section1', 'http://localhost:8085/about#section1'],
            ['http://localhost:8085/es/services#pricing', 'http://localhost:8085/services#pricing'],
            
            // Complex URLs
            ['http://localhost:8085/fr/blog/post-1?utm_source=social#comments', 'http://localhost:8085/blog/post-1?utm_source=social#comments'],
            
            // Edge cases
            ['http://localhost:8085/french', 'http://localhost:8085/french'], // Should not remove non-locale words
            ['http://localhost:8085/eng', 'http://localhost:8085/eng'], // Should not remove partial matches
            
            // Different ports and hosts
            ['https://example.com/fr/page', 'https://example.com/page'],
            ['http://127.0.0.1:3000/en/test', 'http://127.0.0.1:3000/test'],
        ];
    }

    /**
     * @dataProvider addLocaleToUrlProvider
     */
    public function testAddLocaleToUrl(string $inputUrl, string $locale, string $expectedUrl): void
    {
        $result = $this->callPrivateMethod($this->controller, 'addLocaleToUrl', [$inputUrl, $locale]);
        $this->assertEquals($expectedUrl, $result, "Failed to properly add locale '$locale' to URL: $inputUrl");
    }

    public function addLocaleToUrlProvider(): array
    {
        return [
            // Basic locale addition
            ['http://localhost:8085/', 'fr', 'http://localhost:8085/fr'],
            ['http://localhost:8085/about', 'es', 'http://localhost:8085/es/about'],
            ['http://localhost:8085/services', 'de', 'http://localhost:8085/de/services'],
            
            // URLs that already have locale (should not create double prefix)
            ['http://localhost:8085/en', 'fr', 'http://localhost:8085/fr/en'],
            ['http://localhost:8085/fr/about', 'en', 'http://localhost:8085/en/fr/about'],
            
            // URLs with query parameters
            ['http://localhost:8085/blog?page=2', 'fr', 'http://localhost:8085/fr/blog?page=2'],
            ['http://localhost:8085/?search=test', 'es', 'http://localhost:8085/es?search=test'],
            
            // URLs with fragments
            ['http://localhost:8085/about#section1', 'it', 'http://localhost:8085/it/about#section1'],
            ['http://localhost:8085/#top', 'fr', 'http://localhost:8085/fr#top'],
            
            // Complex URLs
            ['http://localhost:8085/blog/post-1?utm_source=social#comments', 'de', 'http://localhost:8085/de/blog/post-1?utm_source=social#comments'],
        ];
    }

    public function testSwitchWithValidLocale(): void
    {
        // Setup mocks
        $this->localizationService
            ->expects($this->once())
            ->method('isLocaleSupported')
            ->with('fr')
            ->willReturn(true);
        
        $this->localizationService
            ->expects($this->once())
            ->method('setLocale')
            ->with('fr');
        
        $this->request
            ->method('isAjax')
            ->willReturn(false);
        
        $this->response
            ->expects($this->once())
            ->method('withRedirect')
            ->willReturnSelf();
        
        // Set referrer URL
        $_SERVER['HTTP_REFERER'] = 'http://localhost:8085/en/about';
        
        // Test switch method
        $params = ['locale' => 'fr'];
        $result = $this->controller->switch($this->request, $this->response, $params);
        
        // Verify session was set
        $this->assertEquals('fr', $_SESSION['locale']);
        
        // The response should be the mocked response
        $this->assertSame($this->response, $result);
    }

    public function testSwitchWithInvalidLocale(): void
    {
        // Setup mocks
        $this->localizationService
            ->expects($this->once())
            ->method('isLocaleSupported')
            ->with('invalid')
            ->willReturn(false);
        
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();
        
        $mockResponse
            ->expects($this->once())
            ->method('withJson')
            ->with([
                'success' => false,
                'message' => 'Unsupported locale'
            ])
            ->willReturnSelf();
        
        $this->response = $mockResponse;
        
        // Test switch method with invalid locale
        $params = ['locale' => 'invalid'];
        $result = $this->controller->switch($this->request, $this->response, $params);
        
        $this->assertSame($this->response, $result);
    }

    public function testSwitchWithAjaxRequest(): void
    {
        // Setup mocks
        $this->localizationService
            ->expects($this->once())
            ->method('isLocaleSupported')
            ->with('es')
            ->willReturn(true);
        
        $this->localizationService
            ->expects($this->once())
            ->method('setLocale')
            ->with('es');
        
        $this->request
            ->method('isAjax')
            ->willReturn(true);
        
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('withJson')
            ->with($this->callback(function ($data) {
                return $data['success'] === true && 
                       $data['locale'] === 'es' && 
                       isset($data['redirect_url']);
            }))
            ->willReturnSelf();
        
        $this->response = $mockResponse;
        
        // Set referrer URL
        $_SERVER['HTTP_REFERER'] = 'http://localhost:8085/fr/services';
        
        // Test AJAX switch
        $params = ['locale' => 'es'];
        $result = $this->controller->switch($this->request, $this->response, $params);
        
        $this->assertSame($this->response, $result);
    }

    /**
     * Test the double locale prefix bug scenario
     */
    public function testDoubleLocalePrefixBugScenario(): void
    {
        // Simulate the bug: user is on /fr page and switches to English
        $_SERVER['HTTP_REFERER'] = 'http://localhost:8085/fr/';
        
        $this->localizationService
            ->expects($this->once())
            ->method('isLocaleSupported')
            ->with('en')
            ->willReturn(true);
        
        $this->localizationService
            ->expects($this->once())
            ->method('setLocale')
            ->with('en');
        
        $this->request
            ->method('isAjax')
            ->willReturn(false);
        
        // Capture the redirect URL to analyze if double prefix occurs
        $redirectUrl = null;
        $this->response
            ->expects($this->once())
            ->method('withRedirect')
            ->with($this->callback(function ($url) use (&$redirectUrl) {
                $redirectUrl = $url;
                return true;
            }))
            ->willReturnSelf();
        
        // Test language switch
        $params = ['locale' => 'en'];
        $this->controller->switch($this->request, $this->response, $params);
        
        // The redirect URL should NOT contain double locale prefix
        $this->assertNotNull($redirectUrl);
        $this->assertStringNotContainsString('/fr/en', $redirectUrl, 'Double locale prefix detected in redirect URL');
        
        // For English (default locale with hide_default_locale=false), it should redirect to /en
        $this->assertEquals('http://localhost:8085/en', $redirectUrl);
    }

    /**
     * Test switching from French to English on a subpage
     */
    public function testLanguageSwitchOnSubpage(): void
    {
        // User is on French about page and switches to English
        $_SERVER['HTTP_REFERER'] = 'http://localhost:8085/fr/about';
        
        $this->localizationService
            ->expects($this->once())
            ->method('isLocaleSupported')
            ->with('en')
            ->willReturn(true);
        
        $this->localizationService
            ->expects($this->once())
            ->method('setLocale')
            ->with('en');
        
        $this->request
            ->method('isAjax')
            ->willReturn(false);
        
        $redirectUrl = null;
        $this->response
            ->expects($this->once())
            ->method('withRedirect')
            ->with($this->callback(function ($url) use (&$redirectUrl) {
                $redirectUrl = $url;
                return true;
            }))
            ->willReturnSelf();
        
        // Test language switch
        $params = ['locale' => 'en'];
        $this->controller->switch($this->request, $this->response, $params);
        
        // Should redirect to English about page, not create double prefix
        $this->assertEquals('http://localhost:8085/en/about', $redirectUrl);
        $this->assertStringNotContainsString('/fr/en', $redirectUrl);
    }

    /**
     * Test locale removal with multiple potential double prefixes
     */
    public function testMultipleLocalePrefixRemoval(): void
    {
        $testCases = [
            'http://localhost:8085/fr/en/about' => 'http://localhost:8085/en/about',
            'http://localhost:8085/en/fr/es/services' => 'http://localhost:8085/fr/es/services',
            'http://localhost:8085/de/it/fr/contact' => 'http://localhost:8085/it/fr/contact',
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $this->callPrivateMethod($this->controller, 'removeLocaleFromUrl', [$input]);
            $this->assertEquals($expected, $result, "Failed to remove first locale from: $input");
        }
    }

    public function testGetAvailableLanguages(): void
    {
        $this->localizationService
            ->expects($this->once())
            ->method('getSupportedLocales')
            ->willReturn(['en', 'fr', 'es']);
        
        $this->localizationService
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('fr');
        
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects($this->once())
            ->method('withJson')
            ->with($this->callback(function ($data) {
                return $data['success'] === true &&
                       $data['current_locale'] === 'fr' &&
                       is_array($data['languages']) &&
                       count($data['languages']) === 3;
            }))
            ->willReturnSelf();
        
        $result = $this->controller->getAvailable($this->request, $mockResponse);
        $this->assertSame($mockResponse, $result);
    }
}