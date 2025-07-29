<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Interfaces\ControllerInterface;

/**
 * Class AboutController
 *
 * Handles requests related to the "About" page.
 * PHP 8.4 compatible with full ControllerInterface implementation.
 */
class AboutController extends Controller implements ControllerInterface
{
    private array $viewData = [];

    /**
     * Handle any initialization logic for the controller.
     */
    public function initialize(): void
    {
        // Set default view data for the about controller with translations
        $this->setViewData([
            'title' => trans('pages.about.title'),
            'metaDescription' => trans('pages.about.meta_description'),
            'controller' => $this->getName()
        ]);
    }

    /**
     * Get the controller's name/identifier.
     */
    public function getName(): string
    {
        return 'about';
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
     * Show the "About" page.
     *
     * @return string
     */
    public function show(): string
    {
        // Ensure controller is initialized
        if (empty($this->viewData)) {
            $this->initialize();
        }

        return View::render('about/show', $this->getViewData());
    }
}
