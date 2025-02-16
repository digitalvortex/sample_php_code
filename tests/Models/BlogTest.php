<?php
//// filepath: /Users/michaelkingsnorth/Development/sample_php_code/tests/Models/BlogTest.php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\Blog;
use PHPUnit\Framework\TestCase;

class BlogTest extends TestCase
{
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

    public function testCreateBlog(): void
    {
        // Use onlyMethods() for "create" (which now returns an int, the new record's ID)
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
        // Assert that the method returns the new record ID (an int)
        $this->assertIsInt($result);
        $this->assertSame($expectedReturn, $result);
    }

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
}