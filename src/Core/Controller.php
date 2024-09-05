<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function render($view, $data = [])
    {
        $viewContent = $this->renderView($view, $data);
        $layoutContent = $this->renderLayout($data);
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    protected function renderView($view, $data)
    {
        ob_start();
        extract($data);
        include __DIR__ . "/../Views/{$view}.php";
        return ob_get_clean();
    }

    protected function renderLayout($data)
    {
        ob_start();
        extract($data);
        include __DIR__ . '/../Views/layouts/main.php';
        return ob_get_clean();
    }
}
