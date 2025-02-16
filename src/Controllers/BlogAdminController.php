<?php

namespace App\Controllers;

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
}