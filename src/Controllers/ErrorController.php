<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Class ErrorController
 *
 * Handles requests related to error pages.
 */
class ErrorController extends Controller
{
    /**
     * Show the 404 Not Found error page.
     *
     * @return void
     */
    public function notFound(): void
    {
        $title = '404 Not Found';
        $content = 'The page you are looking for could not be found.';
        $this->render('error/404', compact('title', 'content'));
    }

    /**
     * Show the 500 Internal Server Error page.
     *
     * @return void
     */
    public function internalError(): void
    {
        $title = '500 Internal Server Error';
        $content = 'An unexpected error occurred. Please try again later.';
        $this->render('error/500', compact('title', 'content'));
    }
}
