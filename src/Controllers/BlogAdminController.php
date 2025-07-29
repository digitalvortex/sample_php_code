<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Traits\ACLControl;
use App\Interfaces\ControllerInterface;

/**
 * Class BlogAdminController
 * 
 * Handles administrative blog operations with access control.
 * PHP 8.4 compatible with full ControllerInterface implementation.
 */
class BlogAdminController extends Controller implements ControllerInterface
{
    use ACLControl;

    private array $viewData = [];

    /**
     * Handle any initialization logic for the controller.
     */
    public function initialize(): void
    {
        // Set default view data for the blog admin controller
        $this->setViewData([
            'controller' => $this->getName(),
            'section' => 'admin'
        ]);
    }

    /**
     * Get the controller's name/identifier.
     */
    public function getName(): string
    {
        return 'blog-admin';
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
     * Show a specific blog post.
     * 
     * @return string
     */
    public function show(): string
    {
        // Ensure controller is initialized
        if (empty($this->viewData)) {
            $this->initialize();
        }

        // Allow all users to view blog posts
        $this->setViewData([
            'title' => 'Blog Post',
            'metaDescription' => 'Read the latest blog post.'
        ]);

        return View::render('blog/show', $this->getViewData());
    }

    /**
     * Show the blog post creation form.
     * 
     * @return string
     */
    public function create(): string
    {
        // Only allow editors or admins to access the create action
        $this->requirePermission(['editor', 'admin']);

        return View::render('blog/create', [
            'title' => 'Create New Blog Post',
            'metaDescription' => 'Create a new blog post for the website.'
        ]);
    }

    /**
     * Show the blog post editing form.
     * 
     * @param int $id Blog post ID
     * @return string
     */
    public function edit(int $id): string
    {
        // Only allow editors or admins to edit a blog
        $this->requirePermission(['editor', 'admin']);

        return View::render('blog/edit', [
            'title' => 'Edit Blog Post',
            'metaDescription' => 'Edit an existing blog post.',
            'blogId' => $id
        ]);
    }

    /**
     * Handle blog post creation submission.
     * 
     * @return string
     */
    public function store(): string
    {
        $this->requirePermission(['editor', 'admin']);

        // TODO: Implement blog post creation logic
        // For now, redirect to success page
        return View::render('blog/created', [
            'title' => 'Blog Post Created',
            'message' => 'Your blog post has been created successfully.'
        ]);
    }

    /**
     * Handle blog post update submission.
     * 
     * @param int $id Blog post ID
     * @return string
     */
    public function update(int $id): string
    {
        $this->requirePermission(['editor', 'admin']);

        // TODO: Implement blog post update logic
        // For now, redirect to success page
        return View::render('blog/updated', [
            'title' => 'Blog Post Updated',
            'message' => 'Your blog post has been updated successfully.',
            'blogId' => $id
        ]);
    }

    /**
     * Handle blog post deletion.
     * 
     * @param int $id Blog post ID
     * @return string
     */
    public function delete(int $id): string
    {
        $this->requirePermission(['admin']);

        // TODO: Implement blog post deletion logic
        // For now, redirect to success page
        return View::render('blog/deleted', [
            'title' => 'Blog Post Deleted',
            'message' => 'The blog post has been deleted successfully.',
            'blogId' => $id
        ]);
    }
}