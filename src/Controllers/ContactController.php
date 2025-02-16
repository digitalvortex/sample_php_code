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

    public function __construct()
    {
        $this->validationResponse = new ValidationResponse();
        $this->csrfToken = new CSRFToken();
    }

    /**
     * Show the "Contact" page.
     */
    public function show(): string
    {
        return View::render('contact/show', [
            'title' => 'Contact Us',
            'content' => 'This is the contact page content.',
            'csrf_token' => $this->csrfToken->generate()
        ]);
    }

    /**
     * Handle the form submission.
     */
    public function submit(): string
    {
        // Verify CSRF token
        if (!$this->csrfToken->verify($_POST['csrf_token'] ?? '')) {
            return \App\Core\View::render('contact/error', [
                'title'   => 'Security Error',
                'message' => 'Invalid security token'
            ]);
        }

        // Use $_POST directly (CLI compatibility) and sanitize manually
        $data = [
            'name'    => isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '',
            'email'   => isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '',
            'message' => isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '',
        ];

        // Validate each field
        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        // Check for empty email or invalid email format.
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }

        if (empty($data['message'])) {
            $errors['message'] = 'Message is required';
        }

        // If there are validation errors, render the contact form with errors.
        if (!empty($errors)) {
            return \App\Core\View::render('contact/show', [
                'title'      => 'Contact Us',
                'content'    => 'This is the contact page content.',
                'csrf_token' => $this->csrfToken->generate(),
                'errors'     => $errors,
                'old'        => $data
            ]);
        }

        // If validation passes, process the form submission and render the success view.
        return \App\Core\View::render('contact/success', [
            'title'         => 'Message Sent',
            'message'       => 'Thank you for your message',
            'includeLayout' => false
        ]);
    }
}