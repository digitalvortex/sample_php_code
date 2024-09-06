<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Response\ValidationResponse;
use App\Interfaces\FormControllerInterface;

/**
 * Class ContactController
 *
 * Handles requests related to the "Contact" page.
 */
class ContactController implements FormControllerInterface
{
    /**
     * Show the "Contact" page.
     *
     * @return string
     */
    public function show(): string
    {
        return View::render('contact/show', [
            'title' => 'Contact Us',
            'content' => 'This is the contact page content.'
        ]);
    }

    public function submit(): string
    {
        $errors = [];
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $message = $_POST['message'] ?? '';

        // Validate name
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }

        // Validate message
        if (empty($message)) {
            $errors['message'] = 'Message is required';
        }

        if (empty($errors)) {
            // Process the form
            // For example: send email, save to database, etc.
            return View::render('contact/success', [
                'title' => 'Thank You',
                'content' => 'Your message has been sent successfully.'
            ]);
        } else {
            // Re-render the form with errors
            return View::render('contact/show', [
                'title' => 'Contact Us',
                'content' => 'This is the contact page content.',
                'errors' => $errors
            ]);
        }
    }
}
