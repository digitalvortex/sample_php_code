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
    private ValidationResponse $validationResponse;

    public function __construct()
    {
        $this->validationResponse = new ValidationResponse();
    }

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
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'message' => $_POST['message'] ?? '',
        ];

        $this->validationResponse->validate($data);
        if ($this->validationResponse->hasErrors()) {
            echo 'Bang!';
            die();
        }

        if (!$this->validationResponse->hasErrors()) {
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
                'errors' => $this->validationResponse->getErrors()
            ]);
        }
    }
}
