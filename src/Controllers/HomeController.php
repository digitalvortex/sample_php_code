<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

/**
 * Class HomeController
 * 
 * Handles requests for the home page.
 */
class HomeController extends Controller
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

    /**
     * Display the about page.
     *
     * @return string The rendered about page
     */
    public function about(): string
    {
        return View::render('about/show', [
            'title' => 'About Us',
            'metaDescription' => 'Learn more about our company and our mission.'
        ]);
    }
}
