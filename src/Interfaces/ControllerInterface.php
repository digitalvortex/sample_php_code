<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface ControllerInterface
 * 
 * Defines the contract for all controllers in the application.
 * PHP 8.4 compatible with strict typing and comprehensive method definitions.
 */
interface ControllerInterface
{
    /**
     * Display the main page/view for this controller.
     * 
     * @return string The rendered view content
     */
    public function show(): string;

    /**
     * Handle any initialization logic for the controller.
     * This method is called before any action method.
     * 
     * @return void
     */
    public function initialize(): void;

    /**
     * Get the controller's name/identifier.
     * 
     * @return string The controller name
     */
    public function getName(): string;

    /**
     * Set data to be passed to views.
     * 
     * @param array<string, mixed> $data Key-value pairs of data
     * @return static For method chaining
     */
    public function setViewData(array $data): static;

    /**
     * Get all view data.
     * 
     * @return array<string, mixed> The view data
     */
    public function getViewData(): array;
}
