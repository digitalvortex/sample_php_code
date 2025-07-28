<!-- 500 Error Page -->
<section class="hero" style="min-height: calc(100vh - 160px); display: flex; align-items: center;">
    <div class="container">
        <div class="hero-content animate-fadeInUp text-center">
            <!-- Error Icon -->
            <div class="feature-icon mb-6">
                <div class="icon-circle" style="width: 120px; height: 120px; margin: 0 auto; background: linear-gradient(135deg, var(--warning) 0%, #f59e0b 100%);">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
            </div>

            <!-- Error Code -->
            <h1 style="font-size: var(--text-5xl); margin-bottom: var(--space-4); background: linear-gradient(135deg, var(--warning), #f59e0b); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                500
            </h1>

            <!-- Error Title -->
            <h2 style="margin-bottom: var(--space-6);">Internal Server Error</h2>

            <!-- Error Message -->
            <p style="font-size: var(--text-lg); margin-bottom: var(--space-8); max-width: 600px; margin-left: auto; margin-right: auto;">
                Oops! Something went wrong on our servers. Don't worry, it's not your fault. 
                Our team has been notified and we're working to fix this issue as quickly as possible.
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
                <a href="javascript:history.back()" class="btn btn-outline btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5"/>
                        <path d="M12 19l-7-7 7-7"/>
                    </svg>
                    Go Back
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Helpful Actions -->
<section class="bg-white" style="padding: var(--space-16) 0;">
    <div class="container">
        <div class="section-header text-center mb-8">
            <h3>What You Can Do</h3>
            <p class="section-subtitle">While we fix this issue, here are some options</p>
        </div>
        
        <div class="grid grid-cols-3">
            <?php 
            $actions = [
                [
                    'title' => 'Try Again Later',
                    'description' => 'This might be a temporary issue that will resolve itself soon.',
                    'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/>',
                    'action' => 'javascript:location.reload()',
                    'button' => 'Refresh Page'
                ],
                [
                    'title' => 'Contact Support',
                    'description' => 'Let us know about this issue so we can investigate further.',
                    'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
                    'action' => '/contact',
                    'button' => 'Contact Us'
                ],
                [
                    'title' => 'Visit Homepage',
                    'description' => 'Start fresh from our homepage and navigate to what you need.',
                    'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/>',
                    'action' => '/',
                    'button' => 'Go Home'
                ]
            ];
            
            foreach ($actions as $index => $action): ?>
                <div class="card animate-fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-4">
                            <div class="icon-circle">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <?= $action['icon'] ?>
                                </svg>
                            </div>
                        </div>
                        <h4><?= $action['title'] ?></h4>
                        <p class="mb-6"><?= $action['description'] ?></p>
                        <a href="<?= $action['action'] ?>" class="btn btn-outline">
                            <?= $action['button'] ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Technical Details (for developers) -->
<section class="bg-gray-50" style="padding: var(--space-12) 0;">
    <div class="container">
        <div class="text-center">
            <details style="max-width: 600px; margin: 0 auto; text-align: left;">
                <summary style="cursor: pointer; font-weight: 600; color: var(--gray-700); padding: var(--space-3); background: white; border-radius: var(--radius-md); margin-bottom: var(--space-4);">
                    Technical Details (for developers)
                </summary>
                <div style="background: var(--gray-900); color: var(--gray-100); padding: var(--space-4); border-radius: var(--radius-md); font-family: var(--font-mono); font-size: var(--text-sm);">
                    <p style="margin-bottom: var(--space-2);"><strong>Error:</strong> HTTP 500 Internal Server Error</p>
                    <p style="margin-bottom: var(--space-2);"><strong>Time:</strong> <?= date('Y-m-d H:i:s T') ?></p>
                    <p style="margin-bottom: var(--space-2);"><strong>Request:</strong> <?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET') ?> <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?></p>
                    <p style="margin-bottom: 0;"><strong>User Agent:</strong> <?= htmlspecialchars(substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 80)) ?>...</p>
                </div>
            </details>
        </div>
    </div>
</section>