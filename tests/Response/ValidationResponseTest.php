<?php

declare(strict_types=1);

namespace Tests\Response;

use App\Response\ValidationResponse;
use PHPUnit\Framework\TestCase;

class ValidationResponseTest extends TestCase
{
    private ValidationResponse $validationResponse;

    protected function setUp(): void
    {
        $this->validationResponse = new ValidationResponse();
    }

    public function testValidateWithValidData(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, world!'
        ];

        $this->validationResponse->validate($data);

        $this->assertFalse($this->validationResponse->hasErrors());
        $this->assertEmpty($this->validationResponse->getErrors());
    }

    public function testValidateWithMissingData(): void
    {
        $data = [];

        $this->validationResponse->validate($data);

        $this->assertTrue($this->validationResponse->hasErrors());
        $errors = $this->validationResponse->getErrors();
        $this->assertCount(3, $errors);
        $this->assertEquals('Name is required', $errors['name']);
        $this->assertEquals('Valid email is required', $errors['email']);
        $this->assertEquals('Message is required', $errors['message']);
    }

    public function testValidateWithInvalidEmail(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'Hello, world!'
        ];

        $this->validationResponse->validate($data);

        $this->assertTrue($this->validationResponse->hasErrors());
        $errors = $this->validationResponse->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('Valid email is required', $errors['email']);
    }

    public function testHasErrorsWithNoErrors(): void
    {
        $this->assertFalse($this->validationResponse->hasErrors());
    }

    public function testGetErrorsWithNoErrors(): void
    {
        $this->assertEmpty($this->validationResponse->getErrors());
    }
}
