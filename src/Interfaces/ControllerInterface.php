<?php

declare(strict_types=1);

namespace App\Interfaces;

interface ControllerInterface
{
    /**
     * Show the page.
     *
     * @return string
     */
    public function show(): string;
}
