<?php

namespace App\Traits;

use InvalidArgumentException;

trait SecurePasswordGenerator
{
    /**
     * Generate a strong random password.
     *
     * @param int $length Length of the password.
     * @return string
     * @throws InvalidArgumentException if the length is less than 1.
     */
    public function generateStrongPassword(int $length = 20): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Password length must be at least 1.');
        }

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}