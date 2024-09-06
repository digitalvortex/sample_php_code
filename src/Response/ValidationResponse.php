<?php

declare(strict_types=1);

namespace App\Response;

class ValidationResponse
{
    private array $errors = [];

    public function validate(array $data): void
    {
        // Validate name
        if (empty($data['name'])) {
            $this->errors['name'] = 'Name is required';
        }

        // Validate email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Valid email is required';
        }

        // Validate message
        if (empty($data['message'])) {
            $this->errors['message'] = 'Message is required';
        }
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
