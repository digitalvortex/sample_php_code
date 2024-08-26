<?php
// tests/SecurePasswordGeneratorTest.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Traits\SecurePasswordGenerator;

class SecurePasswordGeneratorTest extends TestCase
{
    use SecurePasswordGenerator;

    public function testGenerateStrongPasswordDefaultLength()
    {
        $password = $this->generateStrongPassword();
        $this->assertEquals(20, strlen($password), 'Default password length should be 20 characters.');
    }

    public function testGenerateStrongPasswordCustomLength()
    {
        $length = 30;
        $password = $this->generateStrongPassword($length);
        $this->assertEquals($length, strlen($password), "Password length should be $length characters.");
    }

    public function testGenerateStrongPasswordContainsValidCharacters()
    {
        $password = $this->generateStrongPassword();
        $validCharacters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        for ($i = 0; $i < strlen($password); $i++) {
            $this->assertStringContainsString($password[$i], $validCharacters, 'Password contains invalid characters.');
        }
    }

    public function testGenerateStrongPasswordBlank()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password length must be at least 1.');
        $this->generateStrongPassword(0);
    }
}