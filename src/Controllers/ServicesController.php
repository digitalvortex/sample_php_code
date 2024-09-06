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
        $services = [
            'Web Development',
            'Mobile App Development',
            'UI/UX Design',
            'Cloud Solutions',
            'Cybersecurity'
        ];

        return View::render('services/show', [
            'title' => 'Our Services',
            'content' => $services
        ]);
    }
}
