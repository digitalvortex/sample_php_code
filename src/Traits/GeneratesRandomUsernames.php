<?php

namespace App\Traits;

trait GeneratesRandomUsernames
{
    public function generateRandomUsername(): string
    {
        return 'user_' . bin2hex(random_bytes(5));
    }
}