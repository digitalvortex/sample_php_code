<?php

declare(strict_types=1);

namespace App\Response;

use App\Interfaces\ValidationInterface;

/**
 * Validation Response Service
 * 
 * Comprehensive validation service implementing ValidationInterface.
 * PHP 8.4 compatible with flexible validation rules and custom messages.
 */
class ValidationResponse implements ValidationInterface
{
    private array $errors = [];
    private array $validatedData = [];
    private array $customRules = [];
    private array $customMessages = [];

    public function validate(array $data, array $rules): array
    {
        $this->errors = [];
        $this->validatedData = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $fieldRules, $data);
            
            // Add to validated data if no errors
            if (!isset($this->errors[$field])) {
                $this->validatedData[$field] = $value;
            }
        }

        return $this->errors;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    public function addRule(string $name, callable $callback): void
    {
        $this->customRules[$name] = $callback;
    }

    public function setMessages(array $messages): void
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
    }

    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    public function validateValue(mixed $value, string $rule, array $parameters = []): bool
    {
        return $this->applyRule($value, $rule, $parameters) === null;
    }

    public function getAvailableRules(): array
    {
        $builtInRules = [
            'required', 'email', 'numeric', 'integer', 'string', 'boolean',
            'min', 'max', 'between', 'in', 'not_in', 'regex', 'confirmed',
            'alpha', 'alpha_num', 'alpha_dash', 'url', 'ip', 'json',
            'date', 'date_format', 'before', 'after', 'unique', 'exists'
        ];

        return array_merge($builtInRules, array_keys($this->customRules));
    }

    /**
     * Legacy method for backward compatibility.
     */
    public function hasErrors(): bool
    {
        return $this->fails();
    }

    /**
     * Add an error manually.
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /**
     * Clear all errors.
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Set validated data manually.
     */
    public function setValidatedData(array $data): void
    {
        $this->validatedData = $data;
    }

    /**
     * Validate a single field.
     */
    private function validateField(string $field, mixed $value, array $rules, array $allData): void
    {
        foreach ($rules as $rule) {
            $ruleParameters = [];
            
            // Parse rule with parameters (e.g., "min:5" or "between:1,10")
            if (str_contains($rule, ':')) {
                [$ruleName, $paramString] = explode(':', $rule, 2);
                $ruleParameters = array_map('trim', explode(',', $paramString));
            } else {
                $ruleName = $rule;
            }

            $error = $this->applyRule($value, $ruleName, $ruleParameters, $field, $allData);
            
            if ($error !== null) {
                $this->errors[$field] = $this->getErrorMessage($field, $ruleName, $ruleParameters, $error);
                break; // Stop at first error for this field
            }
        }
    }

    /**
     * Apply a validation rule.
     */
    private function applyRule(mixed $value, string $rule, array $parameters = [], string $field = '', array $allData = []): ?string
    {
        // Check custom rules first
        if (isset($this->customRules[$rule])) {
            $result = call_user_func($this->customRules[$rule], $value, $parameters, $field, $allData);
            return $result === true ? null : (is_string($result) ? $result : "Invalid {$field}");
        }

        // Built-in rules
        return match ($rule) {
            'required' => empty($value) && $value !== '0' && $value !== 0 ? "Field is required" : null,
            'email' => !filter_var($value, FILTER_VALIDATE_EMAIL) ? "Must be a valid email address" : null,
            'numeric' => !is_numeric($value) ? "Must be numeric" : null,
            'integer' => !filter_var($value, FILTER_VALIDATE_INT) ? "Must be an integer" : null,
            'string' => !is_string($value) ? "Must be a string" : null,
            'boolean' => !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true) ? "Must be boolean" : null,
            'alpha' => !ctype_alpha($value) ? "Must contain only letters" : null,
            'alpha_num' => !ctype_alnum($value) ? "Must contain only letters and numbers" : null,
            'alpha_dash' => !preg_match('/^[a-zA-Z0-9_-]+$/', $value) ? "Must contain only letters, numbers, dashes and underscores" : null,
            'url' => !filter_var($value, FILTER_VALIDATE_URL) ? "Must be a valid URL" : null,
            'ip' => !filter_var($value, FILTER_VALIDATE_IP) ? "Must be a valid IP address" : null,
            'json' => json_decode($value) === null && json_last_error() !== JSON_ERROR_NONE ? "Must be valid JSON" : null,
            'min' => $this->validateMin($value, $parameters[0] ?? 0),
            'max' => $this->validateMax($value, $parameters[0] ?? 0),
            'between' => $this->validateBetween($value, $parameters[0] ?? 0, $parameters[1] ?? 0),
            'in' => !in_array($value, $parameters, true) ? "Must be one of: " . implode(', ', $parameters) : null,
            'not_in' => in_array($value, $parameters, true) ? "Must not be one of: " . implode(', ', $parameters) : null,
            'regex' => !preg_match($parameters[0] ?? '/.*/', $value) ? "Format is invalid" : null,
            'confirmed' => $this->validateConfirmed($value, $field, $allData),
            'date' => !strtotime($value) ? "Must be a valid date" : null,
            'date_format' => $this->validateDateFormat($value, $parameters[0] ?? 'Y-m-d'),
            'before' => $this->validateBefore($value, $parameters[0] ?? date('Y-m-d')),
            'after' => $this->validateAfter($value, $parameters[0] ?? date('Y-m-d')),
            default => null
        };
    }

    /**
     * Validate minimum value/length.
     */
    private function validateMin(mixed $value, mixed $min): ?string
    {
        if (is_string($value)) {
            return strlen($value) < $min ? "Must be at least {$min} characters" : null;
        } elseif (is_numeric($value)) {
            return $value < $min ? "Must be at least {$min}" : null;
        } elseif (is_array($value)) {
            return count($value) < $min ? "Must have at least {$min} items" : null;
        }
        return null;
    }

    /**
     * Validate maximum value/length.
     */
    private function validateMax(mixed $value, mixed $max): ?string
    {
        if (is_string($value)) {
            return strlen($value) > $max ? "Must be no more than {$max} characters" : null;
        } elseif (is_numeric($value)) {
            return $value > $max ? "Must be no more than {$max}" : null;
        } elseif (is_array($value)) {
            return count($value) > $max ? "Must have no more than {$max} items" : null;
        }
        return null;
    }

    /**
     * Validate between range.
     */
    private function validateBetween(mixed $value, mixed $min, mixed $max): ?string
    {
        $minError = $this->validateMin($value, $min);
        $maxError = $this->validateMax($value, $max);
        
        if ($minError || $maxError) {
            return "Must be between {$min} and {$max}";
        }
        
        return null;
    }

    /**
     * Validate confirmed field.
     */
    private function validateConfirmed(mixed $value, string $field, array $allData): ?string
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $allData[$confirmationField] ?? null;
        
        return $value !== $confirmationValue ? "Confirmation does not match" : null;
    }

    /**
     * Validate date format.
     */
    private function validateDateFormat(mixed $value, string $format): ?string
    {
        $date = \DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value ? null : "Must match format {$format}";
    }

    /**
     * Validate before date.
     */
    private function validateBefore(mixed $value, string $before): ?string
    {
        $valueTime = strtotime($value);
        $beforeTime = strtotime($before);
        
        return $valueTime && $beforeTime && $valueTime < $beforeTime ? null : "Must be before {$before}";
    }

    /**
     * Validate after date.
     */
    private function validateAfter(mixed $value, string $after): ?string
    {
        $valueTime = strtotime($value);
        $afterTime = strtotime($after);
        
        return $valueTime && $afterTime && $valueTime > $afterTime ? null : "Must be after {$after}";
    }

    /**
     * Get error message with custom message support.
     */
    private function getErrorMessage(string $field, string $rule, array $parameters, string $defaultMessage): string
    {
        $key = "{$field}.{$rule}";
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }
        
        $key = $rule;
        if (isset($this->customMessages[$key])) {
            return str_replace(':field', $field, $this->customMessages[$key]);
        }
        
        return ucfirst(str_replace('_', ' ', $field)) . ': ' . $defaultMessage;
    }
}
