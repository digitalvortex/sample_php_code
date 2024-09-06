<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Interfaces\ErrorControllerInterface;

/**
 * Class ErrorController
 *
 * Handles requests related to error pages.
 */
class ErrorController implements ErrorControllerInterface
{
    /**
     * Show the 404 Not Found error page.
     *
     * @return string
     */
    public function notFound(): string
    {
        $title = '404 Not Found';
        $content = 'The page you are looking for could not be found.';
        return View::render('error/404', compact('title', 'content'));
    }

    /**
     * Show the 500 Internal Server Error page.
     *
     * @return string
     */
    public function internalError(): string
    {
        $title = '500 Internal Server Error';
        $content = 'An unexpected error occurred. Please try again later.';
        return View::render('error/500', compact('title', 'content'));
    }

    public function internalServerError(): string
    {
        $title = '500 Internal Server Error';
        $content = 'An unexpected error occurred. Please try again later.';
        return View::render('error/500', compact('title', 'content'));
    }
}
