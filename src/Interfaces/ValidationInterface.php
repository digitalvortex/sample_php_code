<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface ValidationInterface
 * 
 * Defines the contract for validation services.
 * PHP 8.4 compatible with strict typing and comprehensive validation features.
 */
interface ValidationInterface
{
    /**
     * Validate data against rules.
     * 
     * @param array<string, mixed> $data Data to validate
     * @param array<string, array<string, mixed>> $rules Validation rules
     * @return array<string, string> Validation errors (empty if valid)
     */
    public function validate(array $data, array $rules): array;

    /**
     * Check if validation passed (no errors).
     * 
     * @return bool True if validation passed
     */
    public function passes(): bool;

    /**
     * Check if validation failed (has errors).
     * 
     * @return bool True if validation failed
     */
    public function fails(): bool;

    /**
     * Get all validation errors.
     * 
     * @return array<string, string> Validation errors
     */
    public function getErrors(): array;

    /**
     * Get errors for a specific field.
     * 
     * @param string $field Field name
     * @return string|null Error message or null if no error
     */
    public function getError(string $field): ?string;

    /**
     * Add a custom validation rule.
     * 
     * @param string $name Rule name
     * @param callable $callback Validation callback
     * @return void
     */
    public function addRule(string $name, callable $callback): void;

    /**
     * Set custom error messages.
     * 
     * @param array<string, string> $messages Custom error messages
     * @return void
     */
    public function setMessages(array $messages): void;

    /**
     * Get validated data (only fields that passed validation).
     * 
     * @return array<string, mixed> Validated data
     */
    public function getValidatedData(): array;

    /**
     * Validate a single value against a rule.
     * 
     * @param mixed $value Value to validate
     * @param string $rule Validation rule
     * @param array<string, mixed> $parameters Rule parameters
     * @return bool True if value is valid
     */
    public function validateValue(mixed $value, string $rule, array $parameters = []): bool;

    /**
     * Get available validation rules.
     * 
     * @return array<string> List of available rules
     */
    public function getAvailableRules(): array;
}