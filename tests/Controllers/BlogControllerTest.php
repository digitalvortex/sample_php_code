<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\BlogController;

class BlogControllerTest extends TestCase
{

    public function testShow(): void
    {
        $blogController = new BlogController();
        $result = $blogController->show();

        $this->assertIsString($result);
    }
}
