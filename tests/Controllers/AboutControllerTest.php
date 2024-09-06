<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\AboutController;

class AboutControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $aboutController = new AboutController();
        $result = $aboutController->show();

        $this->assertIsString($result);
        $this->assertStringContainsString('About Us', $result);
        //$this->assertEquals('About Us', $result);
    }
}
