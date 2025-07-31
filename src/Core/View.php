<?php

declare(strict_types=1);

namespace App\Core;

/**
 * View Class
 * 
 * Handles view rendering with layout support and data extraction.
 */
class View
{
    /**
     * Render a view template with optional layout.
     * 
     * @param string $template The template name (relative to views directory)
     * @param array<string, mixed> $data Data to pass to the view
     * @return string The rendered HTML content
     * @throws \RuntimeException If template file not found
     */
    public static function render(string $template, array $data = []): string
    {
        // Determine whether to include the layout (default: true)
        $includeLayout = $data['includeLayout'] ?? true;
        // Remove the flag so it won't be extracted into view variables
        unset($data['includeLayout']);

        $templatePath = __DIR__ . '/../views/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template file not found: {$templatePath}");
        }

        // Render the view (the partial) with secure variable passing
        $content = self::renderTemplate($templatePath, $data);

        if ($content === false) {
            throw new \RuntimeException("Failed to render template: {$template}");
        }

        // If the caller does not want the layout, return the partial's content
        if (!$includeLayout) {
            return $content;
        }

        $layoutPath = __DIR__ . '/../views/layouts/main.php';
        
        if (!file_exists($layoutPath)) {
            // If no layout exists, return content without layout
            return $content;
        }

        // Prepare parameters for the layout
        $params = $data;
        $params['content'] = $content;

        // Render the layout with secure template rendering
        $layoutContent = self::renderTemplate($layoutPath, $params);

        if ($layoutContent === false) {
            throw new \RuntimeException("Failed to render layout");
        }

        return $layoutContent;
    }
    
    /**
     * Safely render a template file with data without using extract().
     * 
     * @param string $templatePath Full path to template file
     * @param array<string, mixed> $data Data to make available in template
     * @return string Rendered content
     */
    private static function renderTemplate(string $templatePath, array $data): string
    {
        // Create a closure to isolate template execution scope
        $renderClosure = function (string $__templatePath, array $__data) {
            // Make data available as individual variables in a controlled way
            // We use prefixed variable names to avoid conflicts
            foreach ($__data as $__key => $__value) {
                // Validate variable name to prevent issues
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