<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\ServicesController;

class ServicesControllerTest extends TestCase
{

    public function testShow(): void
    {
        $servicesController = new ServicesController();
        $result = $servicesController->show(1);

        $this->assertIsString($result);
        $this->assertStringContainsString('Service', $result);
    }
}
