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

        // Extract the remaining data so that variables are available in the view
        extract($data);

        // Render the view (the partial)
        ob_start();
        include $templatePath;
        $content = ob_get_clean();

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

        // Render the layout
        ob_start();
        include $layoutPath;
        $layoutContent = ob_get_clean();

        if ($layoutContent === false) {
            throw new \RuntimeException("Failed to render layout");
        }

        return $layoutContent;
    }
}