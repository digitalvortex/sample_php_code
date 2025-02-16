<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use App\Controllers\ContactController;
use App\Response\ValidationResponse;

#[CoversClass(ContactController::class)]
final class ContactControllerTest extends TestCase
{
    private ContactController $controller;

    protected function setUp(): void
    {
        $this->controller = new ContactController();
    }

    #[Test]
    #[TestDox('Can show contact form')]
    public function testShow(): void
    {
        $result = $this->controller->show();

        $this->assertIsString($result);
        $this->assertStringContainsString('<form method="post"', $result);
        $this->assertStringContainsString('action="/contact/submit"', $result);
    }

    #[Test]
    #[TestDox('Can submit valid contact form')]
    public function testSubmit(): void
    {
        $_POST = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message'
        ];

        $result = $this->controller->submit();

        $this->assertIsString($result);
        $this->assertStringContainsString('Thank you for your message', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    #[Test]
    #[TestDox('Shows validation errors for invalid form submission')]
    public function testSubmitWithInvalidData(): void
    {
        $_POST = [
            'name' => '',
            'email' => 'invalid-email',
            'message' => ''
        ];

        $result = $this->controller->submit();

        $this->assertIsString($result);
        $this->assertStringContainsString('error', $result);
        $this->assertStringContainsString('Name is required', $result);
        $this->assertStringContainsString('Valid email is required', $result);
        $this->assertStringContainsString('Message is required', $result);
    }

    #[Test]
    #[TestDox('Handles CSRF token validation')]
    public function testSubmitWithCSRFToken(): void
    {
        $_POST = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'csrf_token' => 'valid_token'
        ];
        
        $_SESSION['csrf_token'] = 'valid_token';

        $result = $this->controller->submit();

        $this->assertIsString($result);
        $this->assertStringContainsString('Thank you for your message', $result);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SESSION = [];
    }
}