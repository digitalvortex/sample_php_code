<?php
declare(strict_types=1);

namespace Tests\Config;

use PHPUnit\Framework\TestCase;
use App\Config\LoadEnv;

class LoadEnvTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(LoadEnv::class));
    }

    public function testLoadMethodExists(): void
    {
        $this->assertTrue(method_exists(LoadEnv::class, 'load'));
    }

    public function testLoadEnvironmentFileExists(): void
    {
        $path = __DIR__ . '/../../.env';
        $this->assertTrue(file_exists($path));
    }

    public function testFileNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LoadEnv::load('non_existent_file.env');
        
    }
}