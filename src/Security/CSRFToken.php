<?php

declare(strict_types=1);

namespace App\Security;

use App\Interfaces\SecurityInterface;

/**
 * CSRF Token and Security Service
 * 
 * Comprehensive security service implementing SecurityInterface.
 * PHP 8.4 compatible with modern security practices.
 */
class CSRFToken implements SecurityInterface
{
    private const SESSION_TOKEN_KEY = '_csrf_token';
    private const TOKEN_LIFETIME = 3600; // 1 hour

    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public function generateCSRFToken(): string
    {
        $token = $this->generateToken();
        
        // Store in session with timestamp
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION[self::SESSION_TOKEN_KEY] = [
            'token' => $token,
            'timestamp' => time()
        ];
        
        return $token;
    }

    public function verifyCSRFToken(string $token, ?string $sessionToken = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $storedData = $_SESSION[self::SESSION_TOKEN_KEY] ?? null;
        
        if (!$storedData || !is_array($storedData)) {
            return false;
        }
        
        // Check if token has expired
        if (time() - $storedData['timestamp'] > self::TOKEN_LIFETIME) {
            unset($_SESSION[self::SESSION_TOKEN_KEY]);
            return false;
        }
        
        $storedToken = $sessionToken ?? $storedData['token'];
        return hash_equals($storedToken, $token);
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function sanitizeInput(mixed $data): mixed
    {
        if (is_string($data)) {
            // Remove null bytes, control characters, and normalize whitespace
            $data = str_replace("\0", '', $data);
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            return trim($data);
        } elseif (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return $data;
    }

    public function escapeHtml(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function escapeUrl(string $string): string
    {
        return urlencode($string);
    }

    public function randomString(int $length = 16, string $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'): string
    {
        $result = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $max)];
        }
        
        return $result;
    }

    public function validateInput(mixed $input, array $rules = []): array
    {
        $errors = [];
        
        if (!is_array($input)) {
            $input = ['value' => $input];
            $rules = ['value' => $rules];
        }
        
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $error = $this->validateRule($value, $rule, $field);
                if ($error) {
                    $errors[$field] = $error;
                    break; // Stop at first error for this field
                }
            }
        }
        
        return $errors;
    }

    /**
     * Legacy method for backward compatibility.
     */
    public function generate(): string
    {
        return $this->generateCSRFToken();
    }

    /**
     * Legacy method for backward compatibility.
     */
    public function verify(string $token): bool
    {
        return $this->verifyCSRFToken($token);
    }

    /**
     * Validate a single rule against a value.
     */
    private function validateRule(mixed $value, string $rule, string $field): ?string
    {
        switch ($rule) {
            case 'required':
                return empty($value) ? "Field {$field} is required" : null;
                
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "Field {$field} must be a valid email" : null;
                
            case 'numeric':
                return !is_numeric($value) ? "Field {$field} must be numeric" : null;
                
            case 'alpha':
                return !ctype_alpha($value) ? "Field {$field} must contain only letters" : null;
                
            case 'alphanumeric':
                return !ctype_alnum($value) ? "Field {$field} must contain only letters and numbers" : null;
                
            case 'no_sql_injection':
                $dangerous = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'UNION', 'SCRIPT'];
                $upperValue = strtoupper($value);
                foreach ($dangerous as $keyword) {
                    if (str_contains($upperValue, $keyword)) {
                        return "Field {$field} contains potentially dangerous content";
                    }
                }
                return null;
                
            case 'no_xss':
                if ($value !== strip_tags($value)) {
                    return "Field {$field} contains potentially dangerous HTML";
                }
                return null;
                
            default:
                if (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    return strlen($value) < $min ? "Field {$field} must be at least {$min} characters" : null;
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    return strlen($value) > $max ? "Field {$field} must be no more than {$max} characters" : null;
                }
                return null;
        }
    }

    /**
     * Generate a cryptographically secure random token for API keys.
     */
    public function generateApiKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Generate a time-based one-time password (TOTP) secret.
     */
    public function generateTotpSecret(): string
    {
        return base32_encode(random_bytes(20));
    }

    /**
     * Simple base32 encoding for TOTP secrets.
     */
    private function base32_encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;
            
            while ($vbits >= 5) {
                $output .= $alphabet[($v >> ($vbits - 5)) & 31];
                $v &= (1 << ($vbits - 5)) - 1;
                $vbits -= 5;
            }
        }
        
        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 31];
        }
        
        return $output;
    }
}