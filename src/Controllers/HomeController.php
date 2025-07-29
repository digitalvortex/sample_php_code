<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\ControllerInterface;
use App\Core\View;

/**
 * Class HomeController
 * 
 * Handles requests for the home page.
 * PHP 8.4 compatible with full ControllerInterface implementation.
 */
class HomeController implements ControllerInterface
{
    private array $viewData = [];

    /**
     * Handle any initialization logic for the controller.
     */
    public function initialize(): void
    {
        // Set default view data for the home controller with translations
        $this->setViewData([
            'title' => trans('pages.home.title'),
            'metaDescription' => trans('pages.home.meta_description'),
            'controller' => $this->getName()
        ]);
    }

    /**
     * Get the controller's name/identifier.
     */
    public function getName(): string
    {
        return 'home';
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
     * Display the home page.
     *
     * @return string The rendered home page
     */
    public function show(): string
    {
        // Ensure controller is initialized
        if (empty($this->viewData)) {
            $this->initialize();
        }

        return View::render('home/index', $this->getViewData());
    }
}
