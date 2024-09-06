<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Interfaces\ControllerInterface;

/**
 * Class AboutController
 *
 * Handles requests related to the "About" page.
 */
class AboutController implements ControllerInterface
{
    /**
     * Show the "About" page.
     *
     * @return string
     */
    public function show(): string
    {
        return View::render('about/show', [
            'title' => 'About Us',
            'metaDescription' => 'Learn more about our company and our mission.'
        ]);
    }
}
