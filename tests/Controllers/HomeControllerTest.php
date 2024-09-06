<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\HomeController;

class HomeControllerTest extends TestCase
{
    public function testShow(): void
    {
        $homeController = new HomeController();
        $result = $homeController->show();

        $this->assertIsString($result);
        $this->assertStringContainsString('Home', $result);
    }
}
