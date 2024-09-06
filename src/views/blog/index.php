<style>
    .blog-post {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .blog-post:last-child {
        border-bottom: none;
    }
</style>

<h1>Blog</h1>

<?php if (empty($blogPosts)): ?>
    <p>No blog posts available at the moment.</p>
<?php else: ?>
    <?php foreach ($blogPosts as $post): ?>
        <article class="blog-post">
            <h2><?= htmlspecialchars($post['title']) ?></h2>
            <p><?= htmlspecialchars($post['content']) ?></p>
        </article>
    <?php endforeach; ?>
<?php endif; ?>