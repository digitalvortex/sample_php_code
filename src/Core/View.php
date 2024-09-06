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
        require __DIR__ . '/../Views/layouts/main.php';
        return ob_get_clean();
    }

    protected static function renderOnlyView($view, $data)
    {
        ob_start();
        extract($data);
        require __DIR__ . "/../Views/$view.php";
        return ob_get_clean();
    }
}
