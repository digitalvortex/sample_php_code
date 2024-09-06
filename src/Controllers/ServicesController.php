<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Interfaces\ControllerInterface;

/**
 * Class ServicesController
 *
 * Handles requests related to the "Services" page.
 */
class ServicesController implements ControllerInterface
{
    /**
     * Show the "Services" page.
     *
     * @return string
     */
    public function show(): string
    {
        return View::render('services/show', [
            'title' => 'Our Services',
            'metaDescription' => 'Explore our range of professional services.'
        ]);
    }
}
