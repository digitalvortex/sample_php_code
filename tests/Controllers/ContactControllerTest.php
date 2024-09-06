<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\ContactController;
use App\Response\ValidationResponse;

class ContactControllerTest extends TestCase
{
    public function testShow(): void
    {
        $contactController = new ContactController();
        $result = $contactController->submit();

        $this->assertIsString($result);
        // You can also add additional assertions to verify the contents of the string
    }

    public function testSubmit(): void
    {
        $contactController = new ContactController();
        $result = $contactController->submit();

        $this->assertIsString($result);
        $this->assertStringContainsString('<div>success</div>', $result);
    }
}
