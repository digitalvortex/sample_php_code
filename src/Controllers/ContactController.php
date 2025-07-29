<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Response\ValidationResponse;
use App\Interfaces\FormControllerInterface;
use App\Security\CSRFToken;

/**
 * Class ContactController
 *
 * Handles requests related to the "Contact" page.
 */
class ContactController implements FormControllerInterface
{
    private ValidationResponse $validationResponse;
    private CSRFToken $csrfToken;
    private array $viewData = [];

    public function __construct(ValidationResponse $validationResponse, CSRFToken $csrfToken)
    {
        $this->validationResponse = $validationResponse;
        $this->csrfToken = $csrfToken;
    }

    /**
     * Handle any initialization logic for the controller.
     */
    public function initialize(): void
    {
        // Set default view data for the contact controller with translations
        $this->setViewData([
            'title' => trans('pages.contact.title'),
            'metaDescription' => trans('pages.contact.meta_description'),
            'controller' => $this->getName()
        ]);
    }

    /**
     * Get the controller's name/identifier.
     */
    public function getName(): string
    {
        return 'contact';
    }

    /**
     * Set data to be passed to views.
     */
    public function setViewData(array $data): static
    {
        $this->viewData = array_merge($this->viewData, $data);
        return $this;
    }

    /**
     * Get all view data.
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    /**
     * Show the "Contact" page.
     */
    public function show(): string
    {
        // Ensure controller is initialized
        if (empty($this->viewData)) {
            $this->initialize();
        }

        $viewData = array_merge($this->getViewData(), [
            'csrf_token' => $this->csrfToken->generate()
        ]);

        return View::render('contact/show', $viewData);
    }

    /**
     * Handle the form submission.
     */
    public function submit(): string
    {
        // Verify CSRF token
        if (!$this->csrfToken->verify($_POST['csrf_token'] ?? '')) {
            return View::render('contact/error', [
                'title'   => trans('pages.contact.error.security_title'),
                'message' => trans('pages.contact.error.invalid_token')
            ]);
        }

        // Use $_POST directly (CLI compatibility) and sanitize manually
        $data = [
            'name'    => isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '',
            'email'   => isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '',
            'message' => isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '',
        ];

        // Validate form data
        $errors = $this->validateForm($data);

        // If there are validation errors, handle them
        if (!empty($errors)) {
            return $this->handleErrors($errors, $data);
        }

        // Process the form data
        if ($this->processForm($data)) {
            return $this->handleSuccess($data);
        } else {
            return View::render('contact/error', [
                'title' => trans('pages.contact.error.processing_title'),
                'message' => trans('pages.contact.error.processing_failed')
            ]);
        }
    }

    /**
     * Validate form data.
     */
    public function validateForm(array $data): array
    {
        $errors = [];
        $rules = $this->getValidationRules();

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? '';

            if (isset($fieldRules['required']) && $fieldRules['required'] && empty($value)) {
                $errors[$field] = trans("pages.contact.validation.{$field}_required");
                continue;
            }

            if (isset($fieldRules['email']) && $fieldRules['email'] && !empty($value)) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = trans("pages.contact.validation.{$field}_invalid");
                }
            }

            if (isset($fieldRules['min_length']) && strlen($value) < $fieldRules['min_length']) {
                $errors[$field] = trans("pages.contact.validation.{$field}_min_length", ['min' => $fieldRules['min_length']]);
            }

            if (isset($fieldRules['max_length']) && strlen($value) > $fieldRules['max_length']) {
                $errors[$field] = trans("pages.contact.validation.{$field}_max_length", ['max' => $fieldRules['max_length']]);
            }
        }

        return $errors;
    }

    /**
     * Process validated form data.
     */
    public function processForm(array $data): bool
    {
        // In a real application, you would:
        // 1. Save to database
        // 2. Send email notification
        // 3. Log the contact request
        // 4. Integrate with CRM systems
        
        // For demonstration purposes, we'll simulate successful processing
        // You could add actual email sending logic here
        
        return true; // Simulate successful processing
    }

    /**
     * Get form validation rules.
     */
    public function getValidationRules(): array
    {
        return [
            'name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 100
            ],
            'email' => [
                'required' => true,
                'email' => true,
                'max_length' => 255
            ],
            'message' => [
                'required' => true,
                'min_length' => 10,
                'max_length' => 1000
            ]
        ];
    }

    /**
     * Handle form submission success.
     */
    public function handleSuccess(array $data): string
    {
        return View::render('contact/success', [
            'title' => trans('pages.contact.success.title'),
            'message' => trans('pages.contact.success.message'),
            'name' => $data['name']
        ]);
    }

    /**
     * Handle form submission errors.
     */
    public function handleErrors(array $errors, array $data): string
    {
        // Ensure controller is initialized
        if (empty($this->viewData)) {
            $this->initialize();
        }

        $viewData = array_merge($this->getViewData(), [
            'csrf_token' => $this->csrfToken->generate(),
            'errors' => $errors,
            'old' => $data
        ]);

        return View::render('contact/show', $viewData);
    }
}