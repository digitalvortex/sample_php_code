<!-- Services Page Header -->
<section class="hero">
    <div class="container">
        <div class="hero-content animate-fadeInUp">
            <h1>Our Services</h1>
            <p>Comprehensive web development solutions designed to elevate your digital presence and drive business growth.</p>
        </div>
    </div>
</section>

<!-- Services Overview -->
<section class="bg-white" style="padding: var(--space-20) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2>What We Offer</h2>
            <p class="section-subtitle">Professional services tailored to your unique requirements</p>
        </div>
        
        <div class="grid grid-cols-3">
            <?php 
            $services = [
                [
                    'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                    'title' => 'Web Development',
                    'description' => 'Custom web applications built with modern PHP, featuring clean architecture, security-first design, and responsive user interfaces.'
                ],
                [
                    'icon' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
                    'title' => 'UI/UX Design',
                    'description' => 'User-centered design solutions that create intuitive, accessible, and visually appealing digital experiences.'
                ],
                [
                    'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                    'title' => 'Security Audits',
                    'description' => 'Comprehensive security assessments to identify vulnerabilities and implement robust protection measures.'
                ],
                [
                    'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
                    'title' => 'API Development',
                    'description' => 'RESTful APIs and microservices designed for scalability, performance, and seamless integration.'
                ],
                [
                    'icon' => '<path d="M9 12l2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V9a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2c0 .552.448 1 1 1s1 .448 1 1v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2c0-.552.448-1 1-1z"/>',
                    'title' => 'Quality Assurance',
                    'description' => 'Comprehensive testing strategies including unit tests, integration tests, and automated testing pipelines.'
                ],
                [
                    'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
                    'title' => 'Code Review',
                    'description' => 'Professional code audits focusing on best practices, performance optimization, and maintainability.'
                ]
            ];
            
            foreach ($services as $index => $service): ?>
                <div class="card animate-fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-4">
                            <div class="icon-circle">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <?= $service['icon'] ?>
                                </svg>
                            </div>
                        </div>
                        <h3><?= $service['title'] ?></h3>
                        <p><?= $service['description'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="bg-gray-50" style="padding: var(--space-20) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2>Our Process</h2>
            <p class="section-subtitle">A proven methodology that delivers exceptional results</p>
        </div>
        
        <div class="grid grid-cols-4">
            <?php 
            $process = [
                ['title' => 'Discovery', 'description' => 'Understanding your goals, requirements, and technical constraints.'],
                ['title' => 'Planning', 'description' => 'Creating detailed project roadmaps and technical specifications.'],
                ['title' => 'Development', 'description' => 'Building robust, scalable solutions with modern best practices.'],
                ['title' => 'Delivery', 'description' => 'Testing, deployment, and ongoing support for your success.']
            ];
            
            foreach ($process as $index => $step): ?>
                <div class="text-center animate-fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="feature-icon mb-4">
                        <div class="icon-circle">
                            <span style="font-weight: 700; font-size: var(--text-xl);"><?= $index + 1 ?></span>
                        </div>
                    </div>
                    <h4><?= $step['title'] ?></h4>
                    <p><?= $step['description'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Technologies Section -->
<section class="bg-white" style="padding: var(--space-20) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h2>Technologies We Use</h2>
            <p class="section-subtitle">Modern tools and frameworks for cutting-edge solutions</p>
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
                    <h4>PHP 8.4</h4>
                    <p>Latest PHP features including strict typing, attributes, and performance enhancements.</p>
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
                    <h4>MVC Architecture</h4>
                    <p>Clean, maintainable code structure following industry-standard patterns.</p>
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
                    <h4>Modern CSS</h4>
                    <p>Responsive design with CSS Grid, Flexbox, and design systems.</p>
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
                    <h4>Testing Frameworks</h4>
                    <p>PHPUnit 11 with comprehensive test coverage and continuous integration.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <div class="container text-center">
        <h2>Ready to Start Your Project?</h2>
        <p>Let's discuss how we can help bring your vision to life with our professional web development services.</p>
        <div class="cta-buttons mt-8">
            <a href="/contact" class="btn btn-primary btn-lg">Get Started Today</a>
            <a href="/about" class="btn btn-outline btn-lg">Learn More About Us</a>
        </div>
    </div>
</section>