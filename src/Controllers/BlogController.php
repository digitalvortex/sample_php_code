<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Interfaces\ControllerInterface;

/**
 * Class BlogController
 * 
 * Handles blog-related requests and responses.
 * PHP 8.4 compatible with full ControllerInterface implementation.
 */
class BlogController extends Controller implements ControllerInterface
{
    private array $viewData = [];

    /**
     * Handle any initialization logic for the controller.
     */
    public function initialize(): void
    {
        // Set default view data for the blog controller with translations
        $this->setViewData([
            'title' => trans('pages.blog.title'),
            'metaDescription' => trans('pages.blog.meta_description'),
            'controller' => $this->getName()
        ]);
    }

    /**
     * Get the controller's name/identifier.
     */
    public function getName(): string
    {
        return 'blog';
    }

    /**
     * Set data to be passed to views.
     */
    public function setViewData(array $data): static
    {
        $this->viewData = array_merge($this->viewData, $data);
        return $this;
    }

    /**
     * Get all view data.
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }

    /**
     * Display the blog index page.
     *
     * @return string
     */
    public function show(): string
    {
        // Ensure controller is initialized
        if (empty($this->viewData)) {
            $this->initialize();
        }

        // In a real application, you would fetch blog posts from a database here
        $blogPosts = [
            ['title' => 'First Blog Post', 'content' => 'This is the content of the first blog post.'],
            ['title' => 'Second Blog Post', 'content' => 'This is the content of the second blog post.'],
        ];

        $this->setViewData(['blogPosts' => $blogPosts]);

        return View::render('blog/show', $this->getViewData());
    }
}
