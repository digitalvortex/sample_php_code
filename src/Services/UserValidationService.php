<?php

declare(strict_types=1);

namespace App\Services;

use App\Response\ValidationResponse;

/**
 * User Validation Service
 * 
 * Centralized validation logic for user authentication and registration.
 * PHP 8.4 compatible with strict typing and comprehensive validation rules.
 */
class UserValidationService
{
    private ValidationResponse $validationResponse;

    public function __construct(ValidationResponse $validationResponse)
    {
        $this->validationResponse = $validationResponse;
    }

    /**
     * Validate user registration data.
     *
     * @param array<string, mixed> $data Registration data to validate
     * @return ValidationResponse Validation result with errors if any
     */
    public function validateRegistration(array $data): ValidationResponse
    {
        $errors = [];

        // Validate username
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (!$this->isValidUsername($data['username'])) {
            $errors['username'] = 'Username must be 3-30 characters, alphanumeric and underscores only';
        }

        // Validate email
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!$this->isValidEmail($data['email'])) {
            $errors['email'] = 'Please provide a valid email address';
        }

        // Validate password
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (!$this->isValidPassword($data['password'])) {
            $errors['password'] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
        }

        // Validate password confirmation
        if (empty($data['password_confirmation'])) {
            $errors['password_confirmation'] = 'Password confirmation is required';
        } elseif ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Password confirmation does not match';
        }

        // Validate first name
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        } elseif (!$this->isValidName($data['first_name'])) {
            $errors['first_name'] = 'First name must be 1-50 characters, letters only';
        }

        // Validate last name
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        } elseif (!$this->isValidName($data['last_name'])) {
            $errors['last_name'] = 'Last name must be 1-50 characters, letters only';
        }

        // Validate terms acceptance (if required)
        if (isset($data['accept_terms']) && !$data['accept_terms']) {
            $errors['accept_terms'] = 'You must accept the terms and conditions';
        }

        $this->validationResponse->setErrors($errors);
        return $this->validationResponse;
    }

    /**
     * Validate user login data.
     *
     * @param array<string, mixed> $data Login data to validate
     * @return ValidationResponse Validation result with errors if any
     */
    public function validateLogin(array $data): ValidationResponse
    {
        $errors = [];

        // Validate email/username
        if (empty($data['email'])) {
            $errors['email'] = 'Email or username is required';
        }

        // Validate password
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        $this->validationResponse->setErrors($errors);
        return $this->validationResponse;
    }

    /**
     * Validate password change data.
     *
     * @param array<string, mixed> $data Password change data to validate
     * @return ValidationResponse Validation result with errors if any
     */
    public function validatePasswordChange(array $data): ValidationResponse
    {
        $errors = [];

        // Validate current password
        if (empty($data['current_password'])) {
            $errors['current_password'] = 'Current password is required';
        }

        // Validate new password
        if (empty($data['new_password'])) {
            $errors['new_password'] = 'New password is required';
        } elseif (!$this->isValidPassword($data['new_password'])) {
            $errors['new_password'] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
        }

        // Validate new password confirmation
        if (empty($data['new_password_confirmation'])) {
            $errors['new_password_confirmation'] = 'New password confirmation is required';
        } elseif ($data['new_password'] !== $data['new_password_confirmation']) {
            $errors['new_password_confirmation'] = 'New password confirmation does not match';
        }

        // Check that new password is different from current
        if (!empty($data['current_password']) && !empty($data['new_password']) && 
            $data['current_password'] === $data['new_password']) {
            $errors['new_password'] = 'New password must be different from current password';
        }

        $this->validationResponse->setErrors($errors);
        return $this->validationResponse;
    }

    /**
     * Validate profile update data.
     *
     * @param array<string, mixed> $data Profile data to validate
     * @return ValidationResponse Validation result with errors if any
     */
    public function validateProfileUpdate(array $data): ValidationResponse
    {
        $errors = [];

        // Validate first name
        if (!empty($data['first_name']) && !$this->isValidName($data['first_name'])) {
            $errors['first_name'] = 'First name must be 1-50 characters, letters only';
        }

        // Validate last name
        if (!empty($data['last_name']) && !$this->isValidName($data['last_name'])) {
            $errors['last_name'] = 'Last name must be 1-50 characters, letters only';
        }

        // Validate email if provided
        if (!empty($data['email']) && !$this->isValidEmail($data['email'])) {
            $errors['email'] = 'Please provide a valid email address';
        }

        $this->validationResponse->setErrors($errors);
        return $this->validationResponse;
    }

    /**
     * Validate username format.
     *
     * @param string $username Username to validate
     * @return bool True if valid username format
     */
    private function isValidUsername(string $username): bool
    {
        return preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username) === 1;
    }

    /**
     * Validate email format.
     *
     * @param string $email Email to validate
     * @return bool True if valid email format
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 255;
    }

    /**
     * Validate password strength.
     *
     * @param string $password Password to validate
     * @return bool True if password meets strength requirements
     */
    private function isValidPassword(string $password): bool
    {
        // At least 8 characters
        if (strlen($password) < 8) {
            return false;
        }

        // At least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // At least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // At least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // At least one special character
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Validate name format (first name, last name).
     *
     * @param string $name Name to validate
     * @return bool True if valid name format
     */
    private function isValidName(string $name): bool
    {
        return preg_match('/^[a-zA-Z\s\'-]{1,50}$/', $name) === 1;
    }

    /**
     * Get password strength score and recommendations.
     *
     * @param string $password Password to analyze
     * @return array<string, mixed> Password strength analysis
     */
    public function getPasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];

        // Length check
        if (strlen($password) >= 8) {
            $score += 25;
        } else {
            $feedback[] = 'Use at least 8 characters';
        }

        // Uppercase letter
        if (preg_match('/[A-Z]/', $password)) {
            $score += 25;
        } else {
            $feedback[] = 'Include uppercase letters';
        }

        // Lowercase letter
        if (preg_match('/[a-z]/', $password)) {
            $score += 25;
        } else {
            $feedback[] = 'Include lowercase letters';
        }

        // Numbers
        if (preg_match('/[0-9]/', $password)) {
            $score += 15;
        } else {
            $feedback[] = 'Include numbers';
        }

        // Special characters
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 10;
        } else {
            $feedback[] = 'Include special characters';
        }

        // Bonus for length
        if (strlen($password) >= 12) {
            $score += 10;
        }

        $strength = match (true) {
            $score >= 90 => 'Very Strong',
            $score >= 70 => 'Strong',
            $score >= 50 => 'Medium',
            $score >= 30 => 'Weak',
            default => 'Very Weak'
        };

        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback,
            'is_valid' => $this->isValidPassword($password)
        ];
    }

    /**
     * Sanitize user input data.
     *
     * @param array<string, mixed> $data Data to sanitize
     * @return array<string, mixed> Sanitized data
     */
    public function sanitizeUserInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace
                $value = trim($value);
                
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Convert special characters to HTML entities for output safety
                // Note: We don't do this for passwords as they should be hashed
                if (!in_array($key, ['password', 'password_confirmation', 'current_password', 'new_password', 'new_password_confirmation'])) {
                    $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                } else {
                    $sanitized[$key] = $value;
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}