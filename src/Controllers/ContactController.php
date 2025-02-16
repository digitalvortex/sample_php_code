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
 *
 * @package App\Controllers
 */
class ContactController implements FormControllerInterface
{
    /**
     * @var ValidationResponse
     */
    private ValidationResponse $validationResponse;

    /**
     * ContactController constructor.
     */
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

    /**
     * Handle the form submission.
     *
     * @return string
     */
    public function submit(): string
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'message' => $_POST['message'] ?? '',
        ];

        $this->validationResponse->validate($data);
        if ($this->validationResponse->hasErrors()) {
            // Re-render the form with errors
            return View::render('contact/show', [
                'title' => 'Contact Us',
                'content' => 'This is the contact page content.',
                'errors' => $this->validationResponse->getErrors()
            ]);
        }

        // Process the form
        // For example: send email, save to database, etc.
        return View::render('contact/success', [
            'title' => 'Thank You',
            'content' => 'Your message has been sent successfully.'
        ]);
    }
}
