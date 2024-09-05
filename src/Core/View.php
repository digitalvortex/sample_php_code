<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $view, array $data = []): string
    {
        $viewContent = self::renderOnlyView($view, $data);
        return self::renderContent($viewContent, $data);
    }

    protected static function renderContent($viewContent, $data)
    {
        $layoutContent = self::layoutContent($data);
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    protected static function layoutContent($data)
    {
        ob_start();
        extract($data);
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
