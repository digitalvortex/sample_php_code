<!-- Blog Page Header -->
<section class="hero">
    <div class="container">
        <div class="hero-content animate-fadeInUp">
            <h1><?= trans('pages.blog.header.title') ?></h1>
            <p><?= trans('pages.blog.header.subtitle') ?></p>
        </div>
    </div>
</section>

<!-- Blog Content -->
<section class="bg-white" style="padding: var(--space-20) 0;">
    <div class="container">
        <?php if (empty($blogPosts)): ?>
            <!-- Empty State -->
            <div class="text-center" style="padding: var(--space-16) 0;">
                <div class="feature-icon mb-6">
                    <div class="icon-circle" style="width: 80px; height: 80px; margin: 0 auto;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10,9 9,9 8,9"/>
                        </svg>
                    </div>
                </div>
                <h2><?= trans('pages.blog.empty.title') ?></h2>
                <p class="section-subtitle mb-8"><?= trans('pages.blog.empty.description') ?></p>
                <a href="/contact" class="btn btn-primary"><?= trans('pages.blog.empty.button') ?></a>
            </div>
        <?php else: ?>
            <!-- Blog Grid -->
            <div class="grid grid-cols-2" style="gap: var(--space-8);">
                <?php foreach ($blogPosts as $index => $post): ?>
                    <article class="card animate-fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                        <div class="card-body">
                            <!-- Blog Meta -->
                            <div class="mb-4" style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--gray-500);">
                                <div style="display: flex; align-items: center; gap: var(--space-1);">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                    <span><?= trans('pages.blog.post.published_on', ['date' => date('M j, Y', strtotime($post['created_at'] ?? 'now'))]) ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: var(--space-1);">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    <span><?= trans('pages.blog.post.by_author', ['author' => htmlspecialchars($post['author'] ?? 'SampleSite Team')]) ?></span>
                                </div>
                            </div>

                            <!-- Blog Title -->
                            <h2 class="mb-4">
                                <a href="/blog/<?= htmlspecialchars($post['slug'] ?? '#') ?>" style="text-decoration: none; color: inherit;">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h2>

                            <!-- Blog Excerpt -->
                            <p class="mb-6"><?= htmlspecialchars(substr($post['content'], 0, 150)) ?>...</p>

                            <!-- Blog Tags -->
                            <?php if (!empty($post['tags'])): ?>
                                <div class="mb-6" style="display: flex; flex-wrap: wrap; gap: var(--space-2);">
                                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                        <span style="background: var(--primary-100); color: var(--primary-700); padding: var(--space-1) var(--space-3); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: 500;">
                                            <?= htmlspecialchars(trim($tag)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Read More Button -->
                            <a href="/blog/<?= htmlspecialchars($post['slug'] ?? '#') ?>" class="btn btn-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14"/>
                                    <path d="M12 5l7 7-7 7"/>
                                </svg>
                                <?= trans('pages.blog.post.read_more') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination (if needed) -->
            <?php if (count($blogPosts) >= 6): ?>
                <div class="text-center mt-12">
                    <div style="display: inline-flex; gap: var(--space-2);">
                        <a href="#" class="btn btn-secondary"><?= trans('pages.blog.pagination.previous') ?></a>
                        <a href="#" class="btn btn-secondary"><?= trans('pages.blog.pagination.next') ?></a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Featured Topics Section -->
<section class="bg-gray-50" style="padding: var(--space-20) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2><?= trans('pages.blog.topics.title') ?></h2>
            <p class="section-subtitle"><?= trans('pages.blog.topics.subtitle') ?></p>
        </div>
        
        <div class="grid grid-cols-3">
            <?php 
            $topics = [
                [
                    'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
                    'title' => trans('pages.blog.topics.php_features.title'),
                    'description' => trans('pages.blog.topics.php_features.description')
                ],
                [
                    'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
                    'title' => trans('pages.blog.topics.mvc_architecture.title'),
                    'description' => trans('pages.blog.topics.mvc_architecture.description')
                ],
                [
                    'icon' => '<path d="M9 12l2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V9a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2c0 .552.448 1 1 1s1 .448 1 1v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2c0-.552.448-1 1-1z"/>',
                    'title' => trans('pages.blog.topics.testing.title'),
                    'description' => trans('pages.blog.topics.testing.description')
                ]
            ];
            
            foreach ($topics as $index => $topic): ?>
                <div class="card animate-fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-4">
                            <div class="icon-circle">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <?= $topic['icon'] ?>
                                </svg>
                            </div>
                        </div>
                        <h3><?= $topic['title'] ?></h3>
                        <p><?= $topic['description'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2><?= trans('pages.blog.newsletter.title') ?></h2>
        <p><?= trans('pages.blog.newsletter.description') ?></p>
        <div class="mt-8">
            <form style="display: inline-flex; gap: var(--space-4); max-width: 400px; width: 100%;">
                <input type="email" placeholder="<?= trans('pages.blog.newsletter.placeholder') ?>" class="form-input" style="flex: 1; margin: 0;">
                <button type="submit" class="btn btn-secondary"><?= trans('pages.blog.newsletter.button') ?></button>
            </form>
        </div>
    </div>
</section>