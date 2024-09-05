<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Class ContractController
 *
 * Handles requests related to the "Contract" page.
 */
class ContactController extends Controller
{
    /**
     * Show the "Contract" page.
     *
     * @return string
     */
    public function show(): string
    {
        return $this->render('contact/show', [
            'title' => 'Contact Us'
        ]);
    }

    public function submit()
    {
        // Handle form submission
        // ...
    }
}
