<?php

namespace App\Controllers;

use App\Core\View;
use App\Traits\ACLControl;

class BlogAdminController
{
    use ACLControl;

    public function create()
    {
        // Only allow editors or admins to access the create action.
        $this->requirePermission(['editor', 'admin']);

        // ... rest of your code to show the create blog view ...
    }

    public function edit(int $id)
    {
        // Only allow editors or admins to edit a blog.
        $this->requirePermission(['editor', 'admin']);

        // ... rest of your code to show the blog editing view ...
    }

    public function show (int $id)
    {
        // Allow all users to view a blog post.
        return View::render('blog/show', [
            'title' => 'Blog Post',
            'metaDescription' => 'Read the latest blog post.'
        ]);
    }
}