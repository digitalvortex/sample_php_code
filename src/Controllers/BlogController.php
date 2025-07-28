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
 */
class BlogController extends Controller implements ControllerInterface
{
    /**
     * Display the blog index page.
     *
     * @return string
     */
    public function show(): string
    {
        // In a real application, you would fetch blog posts from a database here
        $blogPosts = [
            ['title' => 'First Blog Post', 'content' => 'This is the content of the first blog post.'],
            ['title' => 'Second Blog Post', 'content' => 'This is the content of the second blog post.'],
        ];

        return View::render('blog/show', [
            'title' => 'Blog',
            'metaDescription' => 'Read our latest blog posts',
            'blogPosts' => $blogPosts
        ]);
    }
}
