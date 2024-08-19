<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Service\EncryptionService;

/**
 * Class EncryptionTest
 *
 * This class contains unit tests for the Encryption class.
 */
class EncryptionServiceTest extends TestCase
{
    /**
     * @var Encryption The Encryption instance.
     */
    private EncryptionService $encryption;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        // Set a mock environment variable for the encryption key
        $_ENV['KEY'] = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $this->encryption = new EncryptionService();
    }

    /**
     * Test the encryption and decryption process.
     */
    public function testEncryptDecrypt(): void
    {
        $originalValue = 'test_value';
        $encryptedValue = $this->encryption->encrypt($originalValue);
        $decryptedValue = $this->encryption->decrypt($encryptedValue);

        $this->assertNotEquals($originalValue, $encryptedValue, 'Encrypted value should not be the same as the original value.');
        $this->assertEquals($originalValue, $decryptedValue, 'Decrypted value should be the same as the original value.');
    }

    /**
     * Test that an exception is thrown if the key is not set.
     */
    public function testConstructorThrowsExceptionIfKeyNotSet(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Key not set');

        unset($_ENV['KEY']);
        new EncryptionService();
    }

    /**
     * Test that an exception is thrown if the key length is invalid.
     */
    public function testConstructorThrowsExceptionIfKeyLengthInvalid(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Key length must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes');

        $_ENV['KEY'] = base64_encode('invalid_key');
        new EncryptionService();
    }

    /**
     * Test that an exception is thrown if the MAC is invalid during decryption.
     */
    public function testDecryptThrowsExceptionIfMacInvalid(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid MAC');

        $invalidEncryptedValue = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + 10));
        $this->encryption->decrypt($invalidEncryptedValue);
    }
}