<?php

declare(strict_types=1);

namespace App\Controllers;


use App\Core\View;

/**
 * Class BlogController
 * 
 * Handles blog-related requests and responses.
 */
class BlogController
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

        return View::render('blog/index', [
            'title' => 'Blog',
            'metaDescription' => 'Read our latest blog posts',
            'blogPosts' => $blogPosts
        ]);
    }
}
