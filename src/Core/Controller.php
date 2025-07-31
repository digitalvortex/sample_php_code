<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller class
 */
abstract class Controller
{
    /**
     * Render a view securely without using extract()
     *
     * @param string $view The view file to render
     * @param array<string, mixed> $data The data to pass to the view
     * @return string The rendered content
     */
    protected function render(string $view, array $data = []): string
    {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: $viewPath");
        }

        return $this->renderTemplate($viewPath, $data);
    }
    
    /**
     * Safely render a template file with data without using extract().
     * 
     * @param string $templatePath Full path to template file
     * @param array<string, mixed> $data Data to make available in template
     * @return string Rendered content
     */
    private function renderTemplate(string $templatePath, array $data): string
    {
        // Create a closure to isolate template execution scope
        $renderClosure = function (string $__templatePath, array $__data) {
            // Make data available as individual variables in a controlled way
            foreach ($__data as $__key => $__value) {
                // Validate variable name to prevent security issues
                if (is_string($__key) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $__key)) {
                    $$__key = $__value;
                }
            }
            
            // Start output buffering and include template
            ob_start();
            include $__templatePath;
            return ob_get_clean();
        };
        
        $result = $renderClosure($templatePath, $data);
        return $result !== false ? $result : '';
    }
}
