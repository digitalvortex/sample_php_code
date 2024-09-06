<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\ControllerInterface;
use App\Core\View;

/**
 * Class HomeController
 * 
 * Handles requests for the home page.
 */
class HomeController implements ControllerInterface
{
    /**
     * Display the home page.
     *
     * @return string The rendered home page
     */
    public function show(): string
    {
        return View::render('home/index', [
            'title' => 'Welcome to Our Modern Website',
            'metaDescription' => 'Discover amazing features and services on our innovative platform.'
        ]);
    }
}
