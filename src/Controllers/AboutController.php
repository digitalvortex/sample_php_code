<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Class AboutController
 *
 * Handles requests related to the "About" page.
 */
class AboutController extends Controller
{
    /**
     * Show the "About" page.
     *
     * @return string
     */
    public function show(): string
    {
        return $this->render('about/show', [
            'title' => 'About Us'
        ]);
    }
}
