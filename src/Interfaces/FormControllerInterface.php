<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface FormControllerInterface
 * 
 * Defines the contract for controllers that handle form submissions.
 * PHP 8.4 compatible with strict typing and comprehensive method definitions.
 */
interface FormControllerInterface extends ControllerInterface
{
    /**
     * Handle form submission.
     * 
     * @return string The response content (redirect, view, etc.)
     */
    public function submit(): string;

    /**
     * Validate form data.
     * 
     * @param array<string, mixed> $data Form data to validate
     * @return array<string, string> Validation errors (empty if valid)
     */
    public function validateForm(array $data): array;

    /**
     * Process validated form data.
     * 
     * @param array<string, mixed> $data Validated form data
     * @return bool True if processing was successful
     */
    public function processForm(array $data): bool;

    /**
     * Get form validation rules.
     * 
     * @return array<string, array<string, mixed>> Validation rules
     */
    public function getValidationRules(): array;

    /**
     * Handle form submission success.
     * 
     * @param array<string, mixed> $data The processed data
     * @return string Success response
     */
    public function handleSuccess(array $data): string;

    /**
     * Handle form submission errors.
     * 
     * @param array<string, string> $errors Validation errors
     * @param array<string, mixed> $data Original form data
     * @return string Error response
     */
    public function handleErrors(array $errors, array $data): string;
}
