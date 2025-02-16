<?php
declare(strict_types=1);

namespace Tests\Models;

use App\Models\Blog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class BlogTest extends TestCase
{
    #[Test]
    #[TestDox('Can retrieve all blog posts')]
    public function testFindAllBlogs(): void
    {
        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();

        $expected = [
            ['id' => 1, 'title' => 'Test Blog', 'content' => 'Content']
        ];

        $blog->expects($this->once())
             ->method('findAll')
             ->willReturn($expected);

        $this->assertSame($expected, $blog->findAllBlogs());
    }

    #[Test]
    #[TestDox('Can retrieve a blog post by ID')]
    public function testFindBlogById(): void
    {
        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $expected = ['id' => 1, 'title' => 'Test Blog', 'content' => 'Content'];

        $blog->expects($this->once())
             ->method('find')
             ->with(1)
             ->willReturn($expected);

        $this->assertSame($expected, $blog->findBlogById(1));
    }

    #[Test]
    #[TestDox('Can create a new blog post')]
    public function testCreateBlog(): void
    {
        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $data = ['title' => 'New Blog', 'content' => 'Blog Content'];
        $expectedReturn = 2;

        $blog->expects($this->once())
             ->method('create')
             ->with($data)
             ->willReturn($expectedReturn);

        $result = $blog->createBlog($data);
        $this->assertIsInt($result);
        $this->assertSame($expectedReturn, $result);
    }

    #[Test]
    #[TestDox('Can update an existing blog post')]
    public function testUpdateBlog(): void
    {
        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['update'])
            ->getMock();

        $data = ['title' => 'Updated Blog', 'content' => 'Updated Content'];

        $blog->expects($this->once())
             ->method('update')
             ->with(1, $data)
             ->willReturn(true);

        $this->assertTrue($blog->updateBlog(1, $data));
    }

    #[Test]
    #[TestDox('Can delete a blog post')]
    public function testDeleteBlog(): void
    {
        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete'])
            ->getMock();

        $blog->expects($this->once())
             ->method('delete')
             ->with(1)
             ->willReturn(true);

        $this->assertTrue($blog->deleteBlog(1));
    }

    #[Test]
    #[TestDox('Can paginate blog posts')]
    public function testFindBlogsPaginated(): void
    {
        $limit = 2;
        $offset = 0;
        $expected = [
            ['id' => 1, 'title' => 'Blog Post 1'],
            ['id' => 2, 'title' => 'Blog Post 2'],
        ];

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['paginate'])
            ->getMock();

        $blog->expects($this->once())
             ->method('paginate')
             ->with('blogs', $limit, $offset)
             ->willReturn($expected);

        $result = $blog->findBlogsPaginated($limit, $offset);

        $this->assertEquals($expected, $result);
    }
}