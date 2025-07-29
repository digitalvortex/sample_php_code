<!-- Hero Section -->
<section class="hero" id="main-content">
    <div class="container">
        <div class="hero-content animate-fadeInUp">
            <h1><?= trans('pages.home.hero.title') ?></h1>
            <p><?= trans('pages.home.hero.subtitle') ?></p>
            <div class="hero-cta">
                <a href="/services" class="btn btn-primary btn-lg"><?= trans('pages.home.hero.cta_services') ?></a>
                <a href="/about" class="btn btn-outline btn-lg"><?= trans('pages.home.hero.cta_learn_more') ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section bg-white">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2><?= trans('pages.home.features.title') ?></h2>
            <p class="section-subtitle"><?= trans('pages.home.features.subtitle') ?></p>
        </div>
        
        <div class="grid grid-cols-3">
            <div class="card animate-fadeInUp" style="animation-delay: 0.1s;">
                <div class="card-body text-center">
                    <div class="feature-icon mb-4">
                        <div class="icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                        </div>
                    </div>
                    <h3><?= trans('pages.home.features.performance.title') ?></h3>
                    <p><?= trans('pages.home.features.performance.description') ?></p>
                </div>
            </div>
            
            <div class="card animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="card-body text-center">
                    <div class="feature-icon mb-4">
                        <div class="icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <circle cx="12" cy="16" r="1"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                    </div>
                    <h3><?= trans('pages.home.features.security.title') ?></h3>
                    <p><?= trans('pages.home.features.security.description') ?></p>
                </div>
            </div>
            
            <div class="card animate-fadeInUp" style="animation-delay: 0.3s;">
                <div class="card-body text-center">
                    <div class="feature-icon mb-4">
                        <div class="icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                        </div>
                    </div>
                    <h3><?= trans('pages.home.features.responsive.title') ?></h3>
                    <p><?= trans('pages.home.features.responsive.description') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technology Stack Section -->
<section class="tech-section bg-gray-50">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2><?= trans('pages.home.technology.title') ?></h2>
            <p class="section-subtitle"><?= trans('pages.home.technology.subtitle') ?></p>
        </div>
        
        <div class="grid grid-cols-2">
            <div class="tech-feature">
                <div class="tech-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                </div>
                <div>
                    <h4><?= trans('pages.home.technology.php_typing.title') ?></h4>
                    <p><?= trans('pages.home.technology.php_typing.description') ?></p>
                </div>
            </div>
            
            <div class="tech-feature">
                <div class="tech-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <div>
                    <h4><?= trans('pages.home.technology.mvc_architecture.title') ?></h4>
                    <p><?= trans('pages.home.technology.mvc_architecture.description') ?></p>
                </div>
            </div>
            
            <div class="tech-feature">
                <div class="tech-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <div>
                    <h4><?= trans('pages.home.technology.dependency_injection.title') ?></h4>
                    <p><?= trans('pages.home.technology.dependency_injection.description') ?></p>
                </div>
            </div>
            
            <div class="tech-feature">
                <div class="tech-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M21 12c.552 0 1-.448 1-1V9a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2c0 .552.448 1 1 1s1 .448 1 1v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2c0-.552.448-1 1-1z"/>
                    </svg>
                </div>
                <div>
                    <h4><?= trans('pages.home.technology.testing.title') ?></h4>
                    <p><?= trans('pages.home.technology.testing.description') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call-to-Action Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2><?= trans('pages.home.cta.title') ?></h2>
        <p><?= trans('pages.home.cta.subtitle') ?></p>
        <div class="cta-buttons mt-8">
            <a href="/contact" class="btn btn-primary btn-lg"><?= trans('pages.home.cta.contact_button') ?></a>
            <a href="/blog" class="btn btn-outline btn-lg"><?= trans('pages.home.cta.blog_button') ?></a>
        </div>
    </div>
</section>