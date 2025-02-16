<?php
//// filepath: /Users/michaelkingsnorth/Development/sample_php_code/src/Models/Blog.php
namespace App\Models;

use App\Models;

class Blog extends Base
{
    protected string $table = 'blogs';
    
    /**
     * Retrieve all blog posts.
     *
     * @return array
     */
    public function findAllBlogs(): array
    {
        return $this->findAll();
    }
    
    /**
     * Retrieve a single blog post by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findBlogById(int $id): ?array
    {
        return $this->find($id);
    }
    
    /**
     * Create a new blog post.
     *
     * @param array $data
     * @return int New blog post ID
     */
    public function createBlog(array $data): int
    {
        return $this->create($data);
    }
    
    /**
     * Update an existing blog post.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateBlog(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
    
    /**
     * Delete a blog post.
     *
     * @param int $id
     * @return bool
     */
    public function deleteBlog(int $id): bool
    {
        return $this->delete($id);
    }
}