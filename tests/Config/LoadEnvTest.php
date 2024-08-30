<?php
declare(strict_types=1);

namespace Tests\Config;

use PHPUnit\Framework\TestCase;
use App\Config\LoadEnv;

/**
 * Class LoadEnvTest
 *
 * Unit tests for the LoadEnv class.
 */
class LoadEnvTest extends TestCase
{
    /**
     * Test if the LoadEnv class exists.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(LoadEnv::class));
    }

    /**
     * Test if the load method exists in the LoadEnv class.
     *
     * @return void
     */
    public function testLoadMethodExists(): void
    {
        $this->assertTrue(method_exists(LoadEnv::class, 'load'));
    }

    /**
     * Test if the environment file exists.
     *
     * @return void
     */
    public function testLoadEnvironmentFileExists(): void
    {
        $path = __DIR__ . '/../../.env';
        $this->assertTrue(file_exists($path));
    }

    /**
     * Test if an exception is thrown when a non-existent file is loaded.
     *
     * @return void
     */
    public function testFileNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LoadEnv::load('non_existent_file.env');
    }
}