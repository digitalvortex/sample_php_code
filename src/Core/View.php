<?php
namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): string
    {
        // Determine whether to include the layout (default: true)
        $includeLayout = $data['includeLayout'] ?? true;
        // Remove the flag so it won’t be extracted into view variables
        unset($data['includeLayout']);

        // Extract the remaining data so that variables are available in the view.
        extract($data);

        // Render the view (the partial)
        ob_start();
        include __DIR__ . '/../views/' . $template . '.php';
        $content = ob_get_clean();

        // If the caller does not want the layout, return the partial's content.
        if (!$includeLayout) {
            return $content;
        }

        // Prepare parameters for the layout.
        // For example, if your layout expects a $params array, you can do:
        $params = $data;
        $params['content'] = $content;

        // Render the layout.
        ob_start();
        include __DIR__ . '/../views/layouts/main.php';
        return ob_get_clean();
    }
}