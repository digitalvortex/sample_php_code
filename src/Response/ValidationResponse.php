<?php

declare(strict_types=1);

namespace App\Response;

class ValidationResponse
{
    private array $errors = [];

    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $rule) {
            $value = filter_input(INPUT_POST, $field, FILTER_SANITIZE_SPECIAL_CHARS);
            if (!$this->validateField($value, $rule)) {
                $this->errors[$field] = "Invalid $field";
            }
        }
        return empty($this->errors);
    }

    private function validateField($value, $rule): bool
    {
        if ($rule === 'required' && empty($value)) {
            return false;
        }
        if (strpos($rule, 'regex:') === 0) {
            $pattern = substr($rule, 6);
            return preg_match($pattern, $value) === 1;
        }
        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
