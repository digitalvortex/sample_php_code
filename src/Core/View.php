<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $view, array $params = []): string
    {
        $content = self::renderContent($view, $params);
        return self::layoutContent($content, $params);
    }

    protected static function renderContent(string $view, array $params): string
    {
        return self::renderOnlyView($view, $params);
    }

    protected static function layoutContent(string $content, array $params): string
    {
        // Pass $content and $params separately to the layout
        ob_start();
        require __DIR__ . '/../views/layouts/main.php';
        return ob_get_clean();
    }

    protected static function renderOnlyView(string $view, array $params): string
    {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new \Exception("View file not found: $viewPath");
        }

        ob_start();
        extract($params);
        require $viewPath;
        return ob_get_clean();
    }
}
