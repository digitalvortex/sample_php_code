<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($params['title'] ?? 'SampleSite - Modern PHP MVC Framework') ?></title>
    <meta name="description" content="<?= htmlspecialchars($params['metaDescription'] ?? 'A modern PHP MVC framework demonstration with clean architecture and responsive design.') ?>">
    
    <!-- Preconnect to Google Fonts for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Inter font for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="/css/main.css">
    
    <!-- Favicon and PWA meta tags -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="theme-color" content="#0ea5e9">
    
    <!-- Open Graph meta tags for social sharing -->
    <meta property="og:title" content="<?= htmlspecialchars($params['title'] ?? 'SampleSite') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($params['metaDescription'] ?? 'A modern PHP MVC framework demonstration') ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SampleSite">
    
    <!-- JavaScript -->
    <script src="/js/main.js" defer></script>
</head>

<body>
    <div class="page-wrapper">
        <!-- Modern Header with Glassmorphism -->
        <header>
            <div class="container">
                <a href="/" class="logo" aria-label="SampleSite Home">
                    <span class="logo-text">SampleSite</span>
                </a>
                
                <nav role="navigation" aria-label="Main navigation">
                    <button class="menu-toggle" aria-label="Toggle navigation menu" id="mobile-menu-button">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    
                    <ul class="nav-menu" id="nav-menu">
                        <li><a href="/" <?= ($_SERVER['REQUEST_URI'] === '/') ? 'class="active"' : '' ?>>Home</a></li>
                        <li><a href="/about" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/about')) ? 'class="active"' : '' ?>>About</a></li>
                        <li><a href="/services" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/services')) ? 'class="active"' : '' ?>>Services</a></li>
                        <li><a href="/blog" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/blog')) ? 'class="active"' : '' ?>>Blog</a></li>
                        <li><a href="/contact" <?= (str_starts_with($_SERVER['REQUEST_URI'], '/contact')) ? 'class="active"' : '' ?>>Contact</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <!-- Main Content Area -->
        <main role="main">
            <?= $content ?>
        </main>

        <!-- Modern Footer -->
        <footer role="contentinfo">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3 class="footer-title">SampleSite</h3>
                        <p class="footer-description">
                            A modern PHP MVC framework demonstration showcasing clean architecture, 
                            responsive design, and best practices in web development.
                        </p>
                    </div>
                    
                    <div class="footer-section">
                        <h4 class="footer-subtitle">Quick Links</h4>
                        <ul class="footer-links">
                            <li><a href="/">Home</a></li>
                            <li><a href="/about">About</a></li>
                            <li><a href="/services">Services</a></li>
                            <li><a href="/blog">Blog</a></li>
                            <li><a href="/contact">Contact</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4 class="footer-subtitle">Technology</h4>
                        <ul class="footer-links">
                            <li>PHP 8.4</li>
                            <li>MVC Architecture</li>
                            <li>Dependency Injection</li>
                            <li>Responsive Design</li>
                            <li>Modern CSS</li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?= date('Y') ?> SampleSite. Built with ❤️ using modern PHP.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
</body>

</html>