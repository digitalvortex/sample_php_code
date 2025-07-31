<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Pagination;

class Blog extends Base
{
    use Pagination;

    protected static string $table = 'blogs';
    
    /** @var array<string> */
    protected static array $fillable = [
        'title',
        'content',
        'author_id',
        'published',
        'created_at',
        'updated_at'
    ];
    
    /** @var array<string> */
    protected static array $encrypted = [];
    
    /** @var array<string> */
    protected static array $hidden = [];
    
    /**
     * Retrieve all blog posts.
     *
     * @return array<static>
     */
    public static function findAllBlogs(): array
    {
        return static::all();
    }
    
    /**
     * Retrieve a single blog post by ID.
     *
     * @param int $id
     * @return static|null
     */
    public static function findBlogById(int $id): ?static
    {
        return static::find($id);
    }
    
    /**
     * Create a new blog post.
     *
     * @param array<string, mixed> $data
     * @return static New blog post instance
     */
    public static function createBlog(array $data): static
    {
        return static::create($data);
    }
    
    /**
     * Update an existing blog post.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public static function updateBlog(int $id, array $data): bool
    {
        $blog = static::find($id);
        return $blog ? $blog->update($data) : false;
    }
    
    /**
     * Delete a blog post.
     *
     * @param int $id
     * @return bool
     */
    public static function deleteBlog(int $id): bool
    {
        $blog = static::find($id);
        return $blog ? $blog->delete() : false;
    }

    /**
     * Retrieve a paginated list of blog posts.
     *
     * @param int $limit Number of records per page.
     * @param int $offset The offset from where to start retrieving records.
     * @return array<string, mixed>
     */
    public function findBlogsPaginated(int $limit, int $offset): array
    {
        return $this->paginate(static::$table, $limit, $offset);
    }
}