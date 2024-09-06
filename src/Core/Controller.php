<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller class
 */
abstract class Controller
{
    /**
     * Render a view
     *
     * @param string $view The view file to render
     * @param array $data The data to pass to the view
     * @return string The rendered content
     */
    protected function render(string $view, array $data = []): string
    {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: $viewPath");
        }

        ob_start();
        extract($data);
        include $viewPath;
        return ob_get_clean();
    }
}
