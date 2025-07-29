<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Interfaces\ControllerInterface;

/**
 * Class ServicesController
 *
 * Handles requests related to the "Services" page.
 * PHP 8.4 compatible with full ControllerInterface implementation.
 */
class ServicesController extends Controller implements ControllerInterface
{
    private array $viewData = [];

    /**
     * Handle any initialization logic for the controller.
     */
    public function initialize(): void
    {
        // Set default view data for the services controller
        $this->setViewData([
            'title' => 'Our Services',
            'controller' => $this->getName()
        ]);
    }

    /**
     * Get the controller's name/identifier.
     */
    public function getName(): string
    {
        return 'services';
    }

    /**
     * Set data to be passed to views.
     */
    public function setViewData(array $data): static
    {
        $this->viewData = array_merge($this->viewData, $data);
        return $this;
    }

    /**
     * Get all view data.
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    /**
     * Show the "Services" page.
     *
     * @return string
     */
    public function show(): string
    {
        // Ensure controller is initialized
        if (empty($this->viewData)) {
            $this->initialize();
        }

        $services = [
            'Web Development',
            'Mobile App Development',
            'UI/UX Design',
            'Cloud Solutions',
            'Cybersecurity'
        ];

        $this->setViewData(['content' => $services]);

        return View::render('services/show', $this->getViewData());
    }
}
