<?php
declare(strict_types=1);

namespace Tests\Tools;

use PHPUnit\Framework\TestCase;

class SetKeyTest extends TestCase
{
    private string $scriptPath;

    protected function setUp(): void
    {
        $this->scriptPath = __DIR__ . '/../../tools/setkey.php';
    }

    private function getScriptOutput(): string
    {
        ob_start();
        include $this->scriptPath;
        return ob_get_clean();
    }

    public function testScriptOutputsBase64EncodedKey(): void
    {
        $output = $this->getScriptOutput();
        $this->assertMatchesRegularExpression('/Copy this key to your .env file: [A-Za-z0-9+\/=]+/', $output);
    }

    public function testScriptOutputsConfirmationMessage(): void
    {
        $output = $this->getScriptOutput();
        $this->assertStringContainsString('The key has been tested and is a valid sodium key.', $output);
    }

    public function testScriptOutputsWarningMessage(): void
    {
        $output = $this->getScriptOutput();
        $this->assertStringContainsString('WARNING: Keep this key safe and do not add it to your repository or share it with anyone.', $output);
    }
}