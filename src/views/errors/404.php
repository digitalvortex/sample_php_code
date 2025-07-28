<!-- 404 Error Page -->
<section class="hero" style="min-height: calc(100vh - 160px); display: flex; align-items: center;">
    <div class="container">
        <div class="hero-content animate-fadeInUp text-center">
            <!-- Error Icon -->
            <div class="feature-icon mb-6">
                <div class="icon-circle" style="width: 120px; height: 120px; margin: 0 auto; background: linear-gradient(135deg, var(--error) 0%, #dc2626 100%);">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9 9l6 6"/>
                        <path d="M15 9l-6 6"/>
                    </svg>
                </div>
            </div>

            <!-- Error Code -->
            <h1 style="font-size: var(--text-5xl); margin-bottom: var(--space-4); background: linear-gradient(135deg, var(--error), #dc2626); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                404
            </h1>

            <!-- Error Title -->
            <h2 style="margin-bottom: var(--space-6);">Page Not Found</h2>

            <!-- Error Message -->
            <p style="font-size: var(--text-lg); margin-bottom: var(--space-8); max-width: 600px; margin-left: auto; margin-right: auto;">
                Oops! The page you're looking for seems to have gone on vacation. 
                It might have been moved, deleted, or you entered the wrong URL.
            </p>

            <!-- Action Buttons -->
            <div class="hero-cta">
                <a href="/" class="btn btn-primary btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9,22 9,12 15,12 15,22"/>
                    </svg>
                    Go Home
                </a>
                <a href="/contact" class="btn btn-outline btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Helpful Links -->
<section class="bg-white" style="padding: var(--space-16) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h3>Popular Pages</h3>
            <p class="section-subtitle">Maybe you were looking for one of these?</p>
        </div>
        
        <div class="grid grid-cols-4">
            <?php 
            $quickLinks = [
                ['url' => '/', 'title' => 'Home', 'description' => 'Our homepage with latest updates', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/>'],
                ['url' => '/about', 'title' => 'About Us', 'description' => 'Learn about our mission', 'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
                ['url' => '/services', 'title' => 'Services', 'description' => 'Our web development services', 'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'],
                ['url' => '/blog', 'title' => 'Blog', 'description' => 'Latest insights and tutorials', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/>']
            ];
            
            foreach ($quickLinks as $index => $link): ?>
                <div class="text-center animate-fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <a href="<?= $link['url'] ?>" style="text-decoration: none; color: inherit; display: block; padding: var(--space-6); border-radius: var(--radius-lg); transition: all var(--transition-base);" 
                       onmouseover="this.style.background='var(--gray-50)'; this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.background='transparent'; this.style.transform='translateY(0)'">
                        <div class="feature-icon mb-4">
                            <div class="icon-circle">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <?= $link['icon'] ?>
                                </svg>
                            </div>
                        </div>
                        <h4><?= $link['title'] ?></h4>
                        <p style="font-size: var(--text-sm); color: var(--gray-600);"><?= $link['description'] ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>