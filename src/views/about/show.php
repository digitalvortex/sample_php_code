<!-- About Page Header -->
<section class="hero">
    <div class="container">
        <div class="hero-content animate-fadeInUp">
            <h1><?= trans('pages.about.hero.title') ?></h1>
            <p><?= trans('pages.about.hero.subtitle') ?></p>
        </div>
    </div>
</section>

<!-- About Content Section -->
<section class="bg-white" style="padding: var(--space-20) 0;">
    <div class="container">
        <div class="grid grid-cols-2" style="gap: var(--space-12); align-items: center;">
            <div class="animate-fadeInUp">
                <h2><?= trans('pages.about.story.title') ?></h2>
                <p class="mb-6"><?= trans('pages.about.story.paragraph1') ?></p>
                
                <p class="mb-6"><?= trans('pages.about.story.paragraph2') ?></p>
                
                <p><?= trans('pages.about.story.paragraph3') ?></p>
            </div>
            
            <div class="text-center animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="card">
                    <div class="card-body">
                        <div class="feature-icon mb-4">
                            <div class="icon-circle" style="width: 80px; height: 80px;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                        </div>
                        <h3><?= trans('pages.about.quality.title') ?></h3>
                        <p><?= trans('pages.about.quality.description') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="bg-gray-50" style="padding: var(--space-20) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2><?= trans('pages.about.values.title') ?></h2>
            <p class="section-subtitle"><?= trans('pages.about.values.subtitle') ?></p>
        </div>
        
        <div class="grid grid-cols-3">
            <div class="card animate-fadeInUp" style="animation-delay: 0.1s;">
                <div class="card-body text-center">
                    <div class="feature-icon mb-4">
                        <div class="icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"/>
                                <path d="M21 12c.552 0 1-.448 1-1V9a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2c0 .552.448 1 1 1s1 .448 1 1v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2c0-.552.448-1 1-1z"/>
                            </svg>
                        </div>
                    </div>
                    <h3><?= trans('pages.about.values.excellence.title') ?></h3>
                    <p><?= trans('pages.about.values.excellence.description') ?></p>
                </div>
            </div>
            
            <div class="card animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="card-body text-center">
                    <div class="feature-icon mb-4">
                        <div class="icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                    </div>
                    <h3><?= trans('pages.about.values.innovation.title') ?></h3>
                    <p><?= trans('pages.about.values.innovation.description') ?></p>
                </div>
            </div>
            
            <div class="card animate-fadeInUp" style="animation-delay: 0.3s;">
                <div class="card-body text-center">
                    <div class="feature-icon mb-4">
                        <div class="icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                    </div>
                    <h3><?= trans('pages.about.values.collaboration.title') ?></h3>
                    <p><?= trans('pages.about.values.collaboration.description') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technical Expertise Section -->
<section class="bg-white" style="padding: var(--space-20) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2><?= trans('pages.about.technical.title') ?></h2>
            <p class="section-subtitle"><?= trans('pages.about.technical.subtitle') ?></p>
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
                    <h4><?= trans('pages.about.technical.modern_php.title') ?></h4>
                    <p><?= trans('pages.about.technical.modern_php.description') ?></p>
                </div>
            </div>
            
            <div class="tech-feature">
                <div class="tech-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </div>
                <div>
                    <h4><?= trans('pages.about.technical.test_driven.title') ?></h4>
                    <p><?= trans('pages.about.technical.test_driven.description') ?></p>
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
                    <h4><?= trans('pages.about.technical.clean_architecture.title') ?></h4>
                    <p><?= trans('pages.about.technical.clean_architecture.description') ?></p>
                </div>
            </div>
            
            <div class="tech-feature">
                <div class="tech-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <div>
                    <h4><?= trans('pages.about.technical.responsive_design.title') ?></h4>
                    <p><?= trans('pages.about.technical.responsive_design.description') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>